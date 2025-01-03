# DiscordAuth Plugin for PocketMine-MP

A PocketMine-MP plugin that allows players to register their Discord accounts and link them with in-game usernames. The plugin sends a verification code to a configured Discord webhook, which can then be used by players to verify their accounts in-game.

## Features

- Players can link their Discord accounts to their Minecraft usernames.
- Sends a verification message to a Discord channel with a unique verification code.
- Players can use the verification code to complete registration.
- Supports a role command that can be executed in Discord upon successful registration.

## Installation

1. Download the plugin files.
2. Place the `DiscordAuth` folder inside the `plugins` directory of your PocketMine-MP server.
3. Restart the server to allow the plugin to generate its configuration files.

## Configuration

The plugin uses two configuration files:
- `config.yml`: Stores general configuration settings like messages and webhook URL.
- `users.yml`: Stores the registered user data, including their Minecraft usernames and associated Discord IDs.

### Example `config.yml`:

```yaml
# Webhook URL for sending verification messages.
webhook-url: "https://discord.com/api/webhooks/your-webhook-url"

# Messages used throughout the plugin.
verification-title: "Discord Verification"
verification-message: "Player {player_name} is trying to verify their Discord account. Verification code: {verification_code}"
discord-verification-message: "Player {discord_id} is requesting verification with code {verification_code}"
verification-sent: "A verification message has been sent to Discord. Please check your channel."
register-usage: "Usage: /register <verification_code>"
invalid-code: "Invalid verification code."
registration-success: "Your Discord account has been successfully linked!"
already-registered: "This Discord account is already linked to a player."
invalid-discord-id: "Please provide a valid Discord ID."
discord-usage: "Usage: /discord <discord_id>"
code-not-for-player: "The verification code is not for you."
role-command: "/give {player} discord_role"
```

### Example `users.yml`:

```yaml
123456789012345678:
  player: "player_name"
  discord_id: "123456789012345678"
```

## Commands

- `/discord <discord_id>`: Starts the registration process by sending a verification message to the configured Discord webhook.
- `/register <verification_code>`: Completes the registration using the verification code received in Discord.

## How it Works

1. The player uses `/discord <discord_id>` to start the registration process.
2. The plugin generates a random 6-digit verification code and sends it to the Discord webhook.
3. The player receives the code in Discord and uses `/register <verification_code>` to link their Discord account to their Minecraft username.
4. Upon successful registration, the plugin executes a role command (if configured) and stores the Discord ID and player name in the `users.yml` file.


## License

This plugin is released under the MIT License. See the LICENSE file for more details.
