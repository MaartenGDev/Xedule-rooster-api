<?php
namespace App;

use DOMDocument;
use DOMXPath;

class XeduleParser implements XeduleParserInterface
{
    private $document;
    private $xpath;
    private $week = [
        'data' => [
            'lessons' => []
        ]
    ];

    public function __construct()
    {
        $this->document = new DOMDocument();
    }

    private function setupDomDocument($data)
    {
        $this->document->loadHTML($data);
        $this->xpath = new DOMXPath($this->document);
    }

    private function formatDate($date)
    {
        $parts = explode('-', $date);
        $day = $parts[0];
        $month = $parts[1];
        $year = $parts[2];

        return "{$month}/{$day}/{$year}";

    }

    private function getDayDate($day)
    {
        $dayItem = trim($this->xpath->query('//div[@class="dag width1cell"]')->item($day - 1)->nodeValue);

        $dayAndDate = explode(' ', $dayItem);
        $date = $dayAndDate[16];

        return (object)['id' => (int)$day, 'name' => trim($dayAndDate[0]), 'date' => strtotime($this->formatDate($date)), 'display_date' => $date];
    }

    private function contains($haystack, $needle)
    {
        return strpos($haystack, $needle) !== false;
    }

    private function getDay($width)
    {
        return ($width - 2) / 158;
    }

    private function getCssAttribute($needle, $haystack)
    {
        $allAttributes = explode(';', $haystack);

        $attributes = array_filter(
            $allAttributes,
            function ($attribute) use ($needle) {
                return trim(explode(':', $attribute)[0]) === $needle;
            }
        );

        $attributesValues = array_map(
            function ($attribute) {
                return (int)str_replace('px', '', trim(explode(':', $attribute)[1]));
            },
            $attributes
        );

        return array_values($attributesValues)[0];
    }

    public function parse($data)
    {
        $data = str_replace(['&nbsp'], ['&nbsp;'], $data);

        $this->setupDomDocument($data);

        $query = '//div[contains(@class, "main")]//div[contains(@class,"Rooster")]//div[@class = "Les"]';

        $lessons = $this->xpath->query($query);

        $translation = [
            'LesTijden' => 'time',
            'AttendeeBlockColumn_1' => 'room',
            'AttendeeBlockColumn_2' => 'teacher'
        ];

        foreach ($lessons as $lesson) {
            $lessonDetails = (object)
            [
                'name' => '',
                'details' => (object)
                [
                    'time' => (object)
                    [
                        'start' => 0,
                        'end' => 0
                    ],
                    'day' => (object)
                    [
                        'id' => 0,
                        'name' => '',
                        'display_date' => '',
                        'date' => ''
                    ],
                    'room' => '',
                    'teacher' => ''
                ]
            ];

            $style = $lesson->getAttribute('style');
            $width = $this->getCssAttribute('left', $style);
            $day = $this->getDay($width);

            $dayDetails = $this->getDayDate($day);

            $lessonDetails->details->day->id = $dayDetails->id;
            $lessonDetails->details->day->name = $dayDetails->name;
            $lessonDetails->details->day->display_date = $dayDetails->display_date;
            $lessonDetails->details->day->date = $dayDetails->date;

            foreach ($lesson->getElementsByTagName('div') as $detail) {
                $value = trim($detail->nodeValue);
                $class = $detail->getAttribute('class');

                if ($this->contains($class, 'LesTijden')) {
                    $startAndEnd = explode('-', $value);
                    $lessonDetails->details->time->start = $startAndEnd[0];
                    $lessonDetails->details->time->end = $startAndEnd[1];
                }

                if ($this->contains($class, 'LesCode')) {
                    $lessonDetails->name = $value;
                }
                if ($this->contains($class, 'AttendeeBlockColumn_1') || $this->contains($class, 'AttendeeBlockColumn_2')) {
                    $key = $translation[$class];
                    $lessonDetails->details->$key = $value;
                }
            }
            $this->week['data']['lessons'][] = $lessonDetails;
        }
        return $this;
    }

    public function allWeek()
    {
        return $this->week;
    }

}