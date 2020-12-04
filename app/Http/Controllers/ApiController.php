<?php


namespace App\Http\Controllers;


use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Carbon\Carbon;


/**
 * Class ApiController
 * @package App\Http\Controllers
 */
class ApiController extends Controller
{
    /**
     * @var string
     */
    private $ApiBaseUrl = 'https://isdayoff.ru/';

    /**
     * @var string[]
     */
    private $timeRanges = [
        1 => [
            [
                'start' => '10:00',
                'end' => '13:00',
            ],
            [
                'start' => '14:00',
                'end' => '19:00',
            ]
        ],
        2 => [
            [
                'start' => '09:00',
                'end' => '12:00',
            ],
            [
                'start' => '13:00',
                'end' => '18:00',
            ]
        ],
        'corporateParty' => [
            [
                'start' => '10:00',
                'end' => '13:00',
            ],
            [
                'start' => '14:00',
                'end' => '15:00',
            ]
        ],
    ];

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getSchedule(Request $request)
    {
        if ($this->isValidRequest($request)) {

            $startDate = Carbon::parse($request->get('startDate'));
            $endDate = Carbon::parse($request->get('endDate'));
            $userId = (int)$request->get('userId');
            $client = new Client(['base_uri' => $this->ApiBaseUrl]);

            try {
                $response = $client->get("api/getdata?date1={$startDate->format('Ymd')}&date2={$endDate->format('Ymd')}&delimeter=,");
            } catch (ClientException $e) {
                return response()->json(['error' => 'The difference between two dates is more than 366 days, this is a limitation of the API'], $e->getCode());
            }

            $datesStatuses[] = explode(',', $response->getBody()->getContents());
            $dates[] = $startDate->toDateString();

            while (!$startDate->equalTo($endDate)) {
                $dates[] = $startDate->addDay()->toDateString();
            }

            $datesWithStatuses = $this->cleanDates(array_combine($dates, $datesStatuses[0]), $userId, $startDate->year);
            $schedule = [];

            foreach ($datesWithStatuses as $date => $status) {
                $schedule['schedule'][] = [
                    'day' => $date,
                    'timeRanges' => $date == '2018-01-10' ? $this->timeRanges['corporateParty'] : $this->timeRanges[$userId],
                ];
            }

            return response()->json($schedule, 200);
        } else {
            return response()->json(['error' => 'You have missed one or more parameters or they are incorrect'], 400);
        }
    }

    /**
     * @param Request $request
     * @return bool
     */
    private function isValidRequest(Request $request): bool
    {
        //we use 'get' instead of 'has' because 'has' will returns true even if argument is empty
        if (!$request->get('startDate')) {
            return false;
        }

        if (!$request->get('endDate')) {
            return false;
        }

        if (!$request->get('userId')) {
            return false;
        }
        if (!array_key_exists($request->get('userId'), $this->timeRanges)){
            return false;
        }

        return true;
    }

    /**
     * @param array $dates
     * @param int $id
     * @param int $year
     * @return array
     */
    private function cleanDates(array $dates, int $id, int $year): array
    {
        $freeDays = [];

        if ($id == 1) {
            $firstWeekends = [];
            $firstWeekendsStartDate = Carbon::parse("11.01.{$year}");
            $firstWeekendsEndDate = Carbon::parse("25.01.{$year}");
            $firstWeekends[] = $firstWeekendsStartDate->toDateString();

            while (!$firstWeekendsStartDate->equalTo($firstWeekendsEndDate)) {
                $firstWeekends[] = $firstWeekendsStartDate->addDay()->toDateString();
            }

            $secondWeekends = [];
            $secondWeekendsStartDate = Carbon::parse("1.02.{$year}");
            $secondWeekendsEndDate = Carbon::parse("15.02.{$year}");
            $secondWeekends[] = $secondWeekendsStartDate->toDateString();

            while (!$secondWeekendsStartDate->equalTo($secondWeekendsEndDate)) {
                $secondWeekends[] = $secondWeekendsStartDate->addDay()->toDateString();
            }

            $freeDays = array_merge($firstWeekends, $secondWeekends);
        }

        if ($id == 2) {
            $weekendsStartDate = Carbon::parse("01.02.{$year}");
            $weekendsEndDate = Carbon::parse("01.03.{$year}");
            $freeDays[] = $weekendsStartDate->toDateString();

            while (!$weekendsStartDate->equalTo($weekendsEndDate)) {
                $freeDays[] = $weekendsStartDate->addDay()->toDateString();
            }
        }

        if (isset($dates['2018-01-10'])) {
            $dates['2018-01-10'] = '0'; //corporate party
        }

        foreach ($dates as $key => $value) {
            //in response: 0 - work day, 1 - non work day
            if ($value == 1 || in_array($key, $freeDays)) {
                unset($dates[$key]);
            }
        }

        return $dates;
    }

}
