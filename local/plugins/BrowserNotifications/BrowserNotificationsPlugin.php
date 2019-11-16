<?php

defined('GNUSOCIAL') || die();

class BrowserNotificationsPlugin extends Plugin
{
    const PLUGIN_VERSION = '1.0.0';

    public function onEndAccountSettingsNav(Action $action): bool
    {
        $action->elementStart('li');
        $action->element('a', array('href' => common_local_url('browsernotificationssettings')), 'Browser Notifications');
        $action->elementEnd('li');
        return true;
    }

    public function onRouterInitialized(URLMapper $m): bool
    {
        $m->connect(
            'settings/browsernotifications',
            [
                'action' => 'browsernotificationssettings'
            ]
        );

        return true;
    }

    public function onEndShowScripts(Action $action): bool
    {
        $user_settings = BrowserNotificationSettings::getDefaults();

        if (common_logged_in()) {
            $user = common_current_user();

            $bns = BrowserNotificationSettings::getByUserId($user->id);

            if (!empty($bns)) {
                $user_settings = $bns;
            }
        }

        // Only include the JS if the setting is enabled
        if ($user_settings->enabled === true) {
            $action->inlineScript('BrowserNotifications = ' . $user_settings->toJSON());

            $action->script($this->path('js/browser-notifications.js'));
        }

        return true;
    }

    public function onCheckSchema(): bool
    {
        $schema = Schema::get();
        $schema->ensureTable('browser_notifications', BrowserNotificationSettings::schemaDef());
        return true;
    }

    public function onPluginVersion(array &$versions): bool
    {
        $versions[] = [
            'name' => 'BrowserNotifications',
            'version' => self::PLUGIN_VERSION,
            'author' => 'Stéphane Bérubé',
            'homepage' => 'https://code.chromic.org/chimo/gs-browserNotifications',
            'description' =>
            // TRANS: Plugin description.
                _m('Receive browser notifications when a new notice and/or mention comes in.')
        ];
        return true;
    }
}
