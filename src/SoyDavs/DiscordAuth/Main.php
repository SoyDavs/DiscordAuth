<?php

namespace SoyDavs\DiscordAuth;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\event\Listener;

class Main extends PluginBase implements Listener {
    private $pendingRegistrations = [];
    private $config;
    private $usersConfig;

    /**
     * This function is called when the plugin is enabled.
     * It loads the default configuration and registers the event listeners.
     */
    public function onEnable(): void {
        $this->saveDefaultConfig();
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
        $this->usersConfig = new Config($this->getDataFolder() . "users.yml", Config::YAML);
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    /**
     * Sends data to the Discord webhook.
     * @param array $data The data to send.
     * @return bool Whether the request was successful.
     */
    private function sendWebhook(array $data): bool {
        $ch = curl_init($this->getConfig()->get("webhook-url"));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $success = curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
        curl_close($ch);
        return $success;
    }

    /**
     * Validates if the provided Discord ID is valid.
     * @param string $discordId The Discord ID to validate.
     * @return bool Whether the ID is valid.
     */
    private function isValidDiscordId(string $discordId): bool {
        return is_numeric($discordId) && strlen($discordId) >= 17 && strlen($discordId) <= 19;
    }

    /**
     * Handles incoming commands.
     * @param CommandSender $sender The sender of the command.
     * @param Command $command The command object.
     * @param string $label The label used to invoke the command.
     * @param array $args The arguments passed to the command.
     * @return bool Whether the command was handled successfully.
     */
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if (!$sender instanceof Player) {
            $sender->sendMessage($this->config->get("only-in-game"));
            return false;
        }

        switch ($command->getName()) {
            case "discord":
                if (count($args) !== 1) {
                    $sender->sendMessage($this->config->get("discord-usage"));
                    return false;
                }

                $discordId = $args[0];

                // Validate if the Discord ID is valid.
                if (!$this->isValidDiscordId($discordId)) {
                    $sender->sendMessage($this->config->get("invalid-discord-id"));
                    return false;
                }

                // Check if the player is already registered.
                if ($this->usersConfig->exists($discordId)) {
                    $sender->sendMessage($this->config->get("already-registered"));
                    return false;
                }

                // Generate a verification code.
                $code = $this->generateCode();
                $this->pendingRegistrations[$code] = [
                    'player' => $sender->getName(),
                    'discord_id' => $discordId
                ];

                // Send a message to the Discord channel with the verification code.
				// Adjust the placeholders in the code to match the new config.yml settings

                $embed = [
                    "title" => $this->config->get("verification-title"),
                    "description" => str_replace(
                        ["{player_name}", "{verification_code}"],
                        [$sender->getName(), $code],
                        $this->config->get("verification-message")
                    ),
                    "color" => 3447003
                ];

                // Send to Discord
                $this->sendWebhook([
                    "content" => str_replace(
                        ["{discord_id}", "{verification_code}"],
                        [$discordId, $code],
                        $this->config->get("discord-verification-message")
                    ),
                    "embeds" => [$embed]
                ]);


                $sender->sendMessage($this->config->get("verification-sent"));
                return true;

            case "register":
                if (count($args) !== 1) {
                    $sender->sendMessage($this->config->get("register-usage"));
                    return false;
                }

                $code = $args[0];
                if (!isset($this->pendingRegistrations[$code])) {
                    $sender->sendMessage($this->config->get("invalid-code"));
                    return false;
                }

                $registration = $this->pendingRegistrations[$code];
                if ($registration['player'] !== $sender->getName()) {
                    $sender->sendMessage($this->config->get("code-not-for-player"));
                    return false;
                }

                // Save the player's information to the users.yml file.
                $this->usersConfig->set($registration['discord_id'], [
                    'player' => $sender->getName(),
                    'discord_id' => $registration['discord_id']
                ]);
                $this->usersConfig->save();

                // Execute the role command.
                $roleCommand = str_replace(
                    ["{player}", "{discord_id}"],
                    [$sender->getName(), $registration['discord_id']],
                    $this->getConfig()->get("role-command")
                );

                // Send the role command to the Discord channel.
                $this->sendWebhook([
                    "content" => $roleCommand
                ]);

                unset($this->pendingRegistrations[$code]);
                $sender->sendMessage($this->config->get("registration-success"));
                return true;
        }

        return false;
    }

    /**
     * Generates a random 6-digit verification code.
     * @return string The verification code.
     */
    private function generateCode(): string {
        return str_pad(random_int(0, 999999), 6, "0", STR_PAD_LEFT);
    }
}
