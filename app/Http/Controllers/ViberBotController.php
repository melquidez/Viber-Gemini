<?php

namespace App\Http\Controllers;

use GeminiAPI\Laravel\Facades\Gemini;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class ViberBotController extends Controller
{


    // call to set weebhook
    public function setWebhook()
    {
        $client = new Client();

        $url = 'https://chatapi.viber.com/pa/set_webhook';
        $headers = [
            'X-Viber-Auth-Token' => env('VIBER_KEY'),
            'ngrok-skip-browser-warning' => '69420' // prevent the ngrok browser security
        ];
        $body = [
            'url' => env('NGROK_ENDPOINT'), // your ngrok url
        ];

        $response = $client->post($url, [
            'headers' => $headers,
            'json' => $body,
        ]);

        // Log the response for debugging
        Log::channel('viber')->info($response->getBody()->getContents());

        return response()->json(['status' => 'ok']);
    }




    public function webhook(Request $request)
    {
        $data = $request->all();
        Log::channel('viber')->info(json_encode($data)); // Log the incoming data for debugging

        if($data['event'] === 'subscribed'){
            if(Cache::has("subscriber_{$data['user']['id']}")){
                return response()->json(['status' => 'ok']);
            }

            $this->subscribed($data);
        }

        if(isset($data['event']['delivered']) || isset($data['event']['seen'])){
            return;
        }

        if (isset($data['message']['text'])) {
            $message = $data['message']['text'];
            $sender = $data['sender']['id'];
            // $name = $data['sender']['name'];

            // Gemini Method
            $response = $this->generateMessage($message);
            $this->sendMessage($sender, $response); // send the message
        }

        return response()->json(['status' => 'ok']);
    }

    public function subscribed($request){
        $event = $request['event'];
        $userId = $request['user']['id'];
        $name = $request['user']['name'];
        $avatar = $request['user']['avatar'];
        $language = $request['user']['language'];
        $country = $request['user']['country'];


        $message = "Welcome {$name} to Sample Bot! with Gemini Integration.";
        Cache::put("subscriber_{$userId}", true,now()->addSecond(30));
        $this->sendMessage($userId,$message);
        return;
    }



    public function sendMessage($receiver, $text)
    {
        $client = new Client();

        $url = 'https://chatapi.viber.com/pa/send_message';
        $headers = [
            'X-Viber-Auth-Token' => env('VIBER_KEY'), // Replace with your Viber auth token
            'ngrok-skip-browser-warning' => '69420'
        ];
        $body = [
            'receiver' => $receiver,
            'type' => 'text',
            'text' => $text,
        ];

        $response = $client->post($url, [
            'headers' => $headers,
            'json' => $body,
        ]);

        // Log the response for debugging
        Log::channel('viber')->info($response->getBody()->getContents());
    }


    /**
     *
     * function to call the Gemini API
     * @param string $msg user input from viber chat
     * @return string reponse of Gemini API
     */


    public function generateMessage($msg)
    {
        try {
            $response = Gemini::generateText($msg);

            Log::channel('viber')->info('Gemini API Response', [
                'prompt' => $msg,
                'response' => $response,
            ]);

            return $response;
        } catch (RequestException $exception) {
            $statusCode = $exception->getResponse()->getStatusCode();
            $body = (string) $exception->getResponse()->getBody();
            $message = json_decode($body, true)['error']['message'] ?? $exception->getMessage();

            Log::channel('viber')->error('Gemini API Request Exception', [
                'prompt' => $msg,
                'status_code' => $statusCode,
                'error_message' => $message,
                'exception' => $exception,
            ]);
        } catch (Throwable $throwable) {
            Log::channel('viber')->error('Gemini API Error', [
                'prompt' => $msg,
                'error_message' => $throwable->getMessage(),
                'exception' => $throwable,
            ]);
        }

        return "There is something wrong with your message.";
    }
}
