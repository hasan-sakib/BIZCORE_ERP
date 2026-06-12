<?php

declare(strict_types=1);

class AddOauthToUsers
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            ALTER TABLE `users`
                ADD COLUMN `oauth_provider`    VARCHAR(50)  NULL AFTER `remember_token`,
                ADD COLUMN `oauth_provider_id` VARCHAR(255) NULL AFTER `oauth_provider`,
                ADD INDEX `idx_users_oauth` (`oauth_provider`, `oauth_provider_id`)
        ");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("ALTER TABLE `users` DROP INDEX `idx_users_oauth`");
        $pdo->exec("ALTER TABLE `users` DROP COLUMN `oauth_provider_id`");
        $pdo->exec("ALTER TABLE `users` DROP COLUMN `oauth_provider`");
    }
}
