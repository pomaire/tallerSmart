<?php

namespace app\behaviors;

use Yii;
use yii\base\Behavior;
use yii\web\Controller;
use yii\web\TooManyRequestsHttpException;

/**
 * Rate limiting behavior para prevenir ataques de fuerza bruta (HU-015)
 * Limita las requests por IP a 20 por minuto
 */
class RateLimitBehavior extends Behavior
{
    /**
     * @var int Número máximo de requests permitidas por minuto
     */
    public $maxRequests = 20;
    
    /**
     * @var int Período de tiempo en segundos (60 = 1 minuto)
     */
    public $period = 60;
    
    /**
     * {@inheritdoc}
     */
    public function events()
    {
        return [
            Controller::EVENT_BEFORE_ACTION => 'beforeAction',
        ];
    }
    
    /**
     * Verifica el rate limiting antes de ejecutar la acción
     * @param \yii\base\ActionEvent $event
     * @return bool
     * @throws TooManyRequestsHttpException
     */
    public function beforeAction($event)
    {
        // Solo aplicar a acciones de login y API
        $controller = $this->owner;
        $actionId = $controller->action->id;
        
        if (!in_array($actionId, ['login', 'forgot-password', 'reset-password'])) {
            return true;
        }
        
        $ip = Yii::$app->request->userIP;
        $cacheKey = 'rate_limit_' . md5($ip . '_' . $actionId);
        $cache = Yii::$app->cache;
        
        $attempts = $cache->get($cacheKey);
        
        if ($attempts === false) {
            $attempts = 0;
        }
        
        $attempts++;
        $cache->set($cacheKey, $attempts, $this->period);
        
        if ($attempts > $this->maxRequests) {
            $retryAfter = $cache->get('rate_limit_reset_' . md5($ip . '_' . $actionId)) ?? $this->period;
            
            Yii::$app->response->statusCode = 429;
            Yii::$app->response->headers->add('Retry-After', (string)$retryAfter);
            
            throw new TooManyRequestsHttpException(
                "Demasiadas solicitudes. Por favor espere {$retryAfter} segundos antes de intentar nuevamente."
            );
        }
        
        // Guardar el tiempo de reset si es el primer intento
        if ($attempts === 1) {
            $cache->set('rate_limit_reset_' . md5($ip . '_' . $actionId), $this->period, $this->period);
        }
        
        return true;
    }
}
