# ViberBot with GeminiAI Integration Using Laravel PHP Framework

This repository demonstrates the integration of the GeminiAI API into a Viber bot using the Laravel PHP framework. The integration allows the Viber bot to interact with the GeminiAI API to generate responses based on user input.

## Integration Steps

### 1. Setting Viber Webhook

To set the Viber webhook, use the `setWebhook` method in the `ViberBotController`. This method makes a POST request to Viber's `set_webhook` endpoint, configuring the bot to receive incoming messages.

```php
public function setWebhook(){
    #
}
```

Make sure to replace the placeholders in the code with your Viber authentication token (`VIBER_KEY`) and the ngrok endpoint (`NGROK_ENDPOINT`).

### 2. Handling Viber Webhook Events

Incoming Viber webhook events are handled by the `webhook` method in the `ViberBotController`. This method processes various events, such as user subscriptions, message deliveries, and incoming messages.

```php
public function webhook(Request $request){
    #...
}
```

### 3. Subscribing Users

When a user subscribes to the bot (`subscribed` event), a welcome message is sent, and the user is added to the cache to prevent duplicate messages.

```php
public function subscribed($request){
    #
}
```

### 4. Sending Messages

The `sendMessage` method sends text messages to Viber users using the `send_message` endpoint.

```php
public function sendMessage($receiver, $text)
```

### 5. GeminiAI Integration

The `generateMessage` method interacts with the GeminiAI API to generate responses based on user input. It sends a POST request to the Gemini endpoint, passing the user's message and responding with AI generated message.

```php
public function generateMessage($msg)
```

You can set the `GEMINI_API_KEY` environment variable with the API key you obtained from Google AI studio.

Add the following line into your `.env` file.

```
GEMINI_API_KEY='YOUR_GEMINI_API_KEY'
```

### 6. Configure Routes

Finally configure routes that will handle the request, you only call the `set_webhook` route once, and the `webhook` will handle most of the incoming request from users who subscribe to the bot.

```php
// Viber
Route::get('viber/set_webhook', [ViberBotController::class,'setWebhook']); // set webhook
Route::post('viber/webhook', [ViberBotController::class,'webhook']);
```

## Usage

1. Set up your Laravel environment and configure the necessary environment variables.
2. Run the `setWebhook` method to activate the Viber webhook. You can run this on `postman` (this is what i recommended).
3. Handle incoming webhook events and integrate additional logic as needed.
4. Customize or Train the GeminiAI integration based on your specific use case.

Feel free to explore and extend the functionality of this Viber bot with GeminiAI integration!
