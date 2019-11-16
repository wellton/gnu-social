<?php

defined('GNUSOCIAL') || die();

class BrowserNotificationSettings extends Managed_DataObject
{
    public $__table = 'browser_notifications'; // table name

    public $user_id;         // int(10)
    public $enabled;         // boolean
    public $mentions_only;   // boolean

    public static function schemaDef(): array
    {
        return [
            'fields' => [
                'user_id' => ['type' => 'int(10)', 'not null' => true],
                'enabled' => ['type' => 'int', 'size' => 'tiny', 'default' => 1],
                'mentions_only' => ['type' => 'int', 'size' => 'tiny', 'default' => 0],
            ],
            'primary key' => ['user_id'],
            'foreign keys' => [
                'browsernotifications_user_id_fkey' => ['user', ['user_id' => 'id']]
            ]
        ];
    }

    public static function save($user, $settings): void
    {
        $bns = new BrowserNotificationSettings();

        $bns->user_id = $user->id;
        $bns->enabled = $settings['enabled'];
        $bns->mentions_only = $settings['mentions_only'];

        // First time saving settings
        if (empty(self::getByUserId($user->id))) {
            $bns->insert();
        } else { // Updating existing settings
            $bns->update();
        }
    }

    public static function getDefaults(): BrowserNotificationSettings
    {
        $bns = new BrowserNotificationSettings();
        $bns->enabled = true;
        $bns->mentions_only = false;

        return $bns;
    }

    public function toJSON(): string
    {
        return json_encode([
            'enabled' => $this->enabled,
            'mentions_only' => $this->mentions_only
        ]);
    }

    public static function getByUserId(int $userid)
    {
        $user_settings = self::getKV('user_id', $userid);

        $user_settings->enabled = (bool)$user_settings->enabled;
        $user_settings->mentions_only = (bool)$user_settings->mentions_only;

        return $user_settings;
    }
}
