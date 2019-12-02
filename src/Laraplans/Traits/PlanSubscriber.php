<?php

namespace EhsanRasta\Laraplans\Traits;

use Carbon\Carbon;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\App;
use EhsanRasta\Laraplans\SubscriptionBuilder;
use EhsanRasta\Laraplans\Contracts\PlanInterface;
use EhsanRasta\Laraplans\SubscriptionUsageManager;
use EhsanRasta\Laraplans\Contracts\PlanSubscriptionInterface;
use EhsanRasta\Laraplans\Contracts\SubscriptionBuilderInterface;
use EhsanRasta\Laraplans\Contracts\SubscriptionResolverInterface;

trait PlanSubscriber
{
    /**
     * Get a subscription by name.
     *
     * @param  string $name
     * @return \EhsanRasta\Laraplans\Models\PlanSubscription|null
     */
    public function subscription($name = 'default')
    {
        return App::make(SubscriptionResolverInterface::class)->resolve($this, $name);
    }

    /**
     * Get user plan subscription.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function subscriptions()
    {
        return $this->morphMany(config('laraplans.models.plan_subscription'), 'subscribable');
    }

    /**
     * Check if the user has a given subscription.
     *
     * @param  string $subscription
     * @param  int $planId
     * @return bool
     */
    public function subscribed($subscription = 'default', $planId = null)
    {
        $subscription = $this->subscription($subscription);

        if (is_null($subscription)) {
            return false;
        }

        if (is_null($planId)) {
            return $subscription->isActive();
        }

        if ($planId == $subscription->plan_id and $subscription->isActive()) {
            return true;
        }

        return false;
    }

    /**
     * Subscribe user to a new plan.
     *
     * @param string $subscription
     * @param mixed $plan
     * @return \EhsanRasta\Laraplans\Models\PlanSubscription
     */
    public function newSubscription($subscription, $plan)
    {
        $container = Container::getInstance();

        if (method_exists($container, 'makeWith')) {
            return $container->makeWith(SubscriptionBuilderInterface::class, [
                'user' => $this, 'name' => $subscription, 'plan' => $plan
            ]);
        }

        return $container->make(SubscriptionBuilderInterface::class, [$this, $subscription, $plan]);
    }

    /**
     * Get subscription usage manager instance.
     *
     * @param  string $subscription
     * @return \EhsanRasta\Laraplans\SubscriptionUsageManager
     */
    public function subscriptionUsage($subscription = 'default')
    {
        return new SubscriptionUsageManager($this->subscription($subscription));
    }
}
