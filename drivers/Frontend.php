<?php

namespace PBS\Logout\Drivers;

use App;
use Event;
use Carbon\Carbon;
use Rainlab\User\Models\User;
use Rainlab\User\Facades\Auth;
use PBS\Logout\Models\Settings;
use PBS\Logout\Contracts\Driver;
use PBS\Logout\Drivers\Frontend\Middleware;

class Frontend extends BaseDriver implements Driver
{
    /**
     * Return the user model.
     *
     * @return \Rainlab\User\Models\User
     */
    public function model()
    {
        return User::class;
    }

    /**
     * Return the Authentication facade.
     *
     * @return \Rainlab\User\Facades\Auth
     */
    public function facade()
    {
        return Auth::class;
    }

    /**
     * Boot the needed driver operations.
     *
     * @return self
     */
    public function boot()
    {
        if (!Settings::instance()->frontend_enabled) {
            return $this;
        }

        // Check if we are currently in frontend.
        if (App::runningInBackend()) {
            return $this;
        }

        // The middleware that's responsible for updating user's activity.
        // We're updating the last activity of the user with each request.
        $this->app['Illuminate\Contracts\Http\Kernel']->pushMiddleware(Middleware::class);

        // Extending the user model to add a method that
        // updates the last activity so the middleware
        // can use it every request.
        $this->model()::extend(function ($model) {
            $model->addDynamicMethod('updateActivity', function () use ($model) {
                $model->pbs_logout_last_activity = Carbon::now();
                $model->save();
            });
        });

        return $this;
    }

    /**
     * Add the settings for this driver.
     *
     * @return void
     */
    public function settings()
    {
        Event::listen('backend.form.extendFields', function ($widget) {
            if (!$widget->model instanceof Settings) {
                return;
            }

            $widget->addFields([
                'frontend_enabled' => [
                    'label' => 'Enable Frontend Auto-Logout',
                    'comment' => "This option will auto-logout users who leave the site entirely. This won't work unless you install Rainlab User Plugin.",
                    'type' => 'checkbox',
                    'span' => 'left',
                    'default' => false,
                ],
                'frontend_allowed_inactivity' => [
                    'label' => 'Users Mins of inactivity',
                    'comment' => 'The number of minutes of inactivity allowed before the frontend user gets logged out. Make it zero if you don\'nt want to logout the user after amount of inactivity time.',
                    'span' => 'right',
                    'default' => 0,
                    'trigger' => [
                        'action' => 'show',
                        'field' => 'frontend_enabled',
                        'condition' => 'checked'
                    ]
                ],
                'frontend_popup_custom_class' => [
                    'label' => 'Frontend Popup Custom Class',
                    'comment' => 'If you want to add a custom class for the warning modal in frontend.',
                    'span' => 'right',
                    'default' => 0,
                    'trigger' => [
                        'action' => 'show',
                        'field' => 'frontend_enabled',
                        'condition' => 'checked'
                    ]
                ],
            ]);
        });
    }
}
