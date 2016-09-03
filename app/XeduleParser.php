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

    private function getDayDate($day)
    {

        return $this->xpath->query('//div[@class="width1cell"]');
    }

    // Todo: move to helper file.
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
            'LesCode' => 'lesson',
            'LesTijden' => 'time',
            'AttendeeBlockColumn_1' => 'room',
            'AttendeeBlockColumn_2' => 'teacher'
        ];

        foreach ($lessons as $lesson) {
            $lessonDetails = (object)['lesson' => '', 'time' => (object)['start' => 0, 'end' => 0], 'day' => 0, 'room' => '', 'teacher' => ''];

            $style = $lesson->getAttribute('style');
            $width = $this->getCssAttribute('left', $style);
            $day = $this->getDay($width);

            $lessonDetails->day = $day;
            $lessonDetails->date = $this->getDayDate($day);

            foreach ($lesson->getElementsByTagName('div') as $detail) {
                $value = trim($detail->nodeValue);
                $class = $detail->getAttribute('class');

                if ($this->contains($class, 'LesTijden')) {
                    $startAndEnd = explode('-', $value);
                    $lessonDetails->time->start = $startAndEnd[0];
                    $lessonDetails->time->end = $startAndEnd[1];
                }

                if ($this->contains($class, 'LesCode') || $this->contains($class, 'AttendeeBlockColumn_1') || $this->contains($class, 'AttendeeBlockColumn_2')) {
                    $key = $translation[$class];
                    $lessonDetails->$key = $value;
                }
            }
            $this->week['data']['lessons'][] = $lessonDetails;
        }
        return $this;
    }

    public function allWeek(){
        return $this->week;
    }

}