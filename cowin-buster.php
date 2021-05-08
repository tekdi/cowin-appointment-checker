<?php
$config = include(__DIR__ . '/config.php');

$msg = "";

foreach ($config['pinCodes'] as $pincode)
{
    $config['daysCount'] = (INT) $config['daysCount'];

    for ($k = $config['daysCount']; $k >= 0; $k--)
    {
        $date = date('d-m-Y', strtotime('+' . $k . ' days'));
        $url = "https://cdn-api.co-vin.in/api/v2/appointment/sessions/public/calendarByPin?pincode=" . $pincode . "&date=" . $date;

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $headers = array(
            "Accept: application/json",
            "user-agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.72 Safari/537.36"
        );

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($curl);
        curl_close($curl);

        $response = json_decode($response);

        if (count($response->centers))
        {
            foreach($response->centers as $center)
            {
                if (count($center->sessions))
                {
                    foreach ($center->sessions as $session)
                    {
                        if ($session->min_age_limit >= 18 && $session->min_age_limit < 45 && $session->available_capacity > 0)
                        {
                            $formattedDate = date_format(date_create($date), "j F Y");
                            $msg .= "Slot available on " . $formattedDate . " at " . $center->name . " (" . $center->pincode . "), " . $center->district_name . ", " . $center->state_name . " For " . $session->vaccine . "\n";
                        }
                    }
                }
            }
        }
    }
}

if ($msg != "" && !empty($config['slackChannel']))
{
    $updateTime = date('d M, H:i');

    foreach ($config['slackChannel'] as $slackChannel)
    {
        if (!empty($slackChannel['user']) && !empty($slackChannel['channel']) && !empty($slackChannel['url']))
        {
            $postRequest = array(
                'username' => $slackChannel['user'],
                'channel' => $slackChannel['channel'],
                'text' => "Check done at " . $updateTime . " Hrs \n" . $msg
            );
    
            $slackData = array('payload' => json_encode($postRequest));
    
            $cURLConnection = curl_init($slackChannel['url']);
            curl_setopt($cURLConnection, CURLOPT_POSTFIELDS, $slackData);
            curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
    
            $apiResponse = curl_exec($cURLConnection);
            curl_close($cURLConnection);
        }
    }
}
