<?php

defined('GNUSOCIAL') || die();

class BrowserNotificationsSettingsAction extends SettingsAction
{
    protected function doPost()
    {
        $settings = array(
            'enabled' => $this->boolean('enabled', false),
            'mentions_only' => $this->boolean('mentions_only', false)
        );

        BrowserNotificationSettings::save(common_current_user(), $settings);

        return _m('Settings saved.');
    }

    public function title()
    {
        return _m('Browser Notifications Settings');
    }

    public function showContent()
    {
        $form = new BrowserNotificationsSettingsForm($this);
        $form->show();
    }
}
