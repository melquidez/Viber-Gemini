<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

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
        $client = new Client();// make a new guzzle Clietn


        // check if endpoint is for chat endpoint or text base endpoint
        $prompt = [
            "contents"              => [
                [
                    "parts"         => [
                        [
                            "text"  => $msg
                        ]
                    ]
                ]
            ]
        ];

        try {
            $response = $client->post(env('GEMINI_ENDPOINT'), [
                'headers' => ['Content-Type' => 'application/json'],
                'query' => ['key' => env('GEMINI_API_KEY')],
                'json' => $prompt,
            ]);

            $responseData = json_decode($response->getBody(), true);
            Log::channel('viber')->info('Gemini API Response: ' . json_encode($responseData));
            return $responseData['candidates'][0]['content']['parts'][0]['text'];

        } catch (\GuzzleHttp\Exception\RequestException $exception) {
            $statusCode = $exception->getResponse()->getStatusCode();
            $message = json_decode($exception->getResponse()->getBody(), true)['error']['message'] ?? $exception->getMessage();

            return "There is something wrong with your message.";
        } catch (\Exception $exception) {
            return "There is something wrong with your message.";
            // return
        }
    }


    // -H 'Content-Type: application/json' \
    // -X POST \
    // -d '{
    //   "contents": [{
    //     "parts":[{
    //       "text": "Write a story about a magic backpack."}]}]}' 2> /dev/null
}



