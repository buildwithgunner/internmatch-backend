<?php

namespace App\Traits;

trait HandlesCategorization
{
    /**
     * Define umbrella groups for various academic and professional fields.
     */
    public static $categoryUmbrellas = [
        'Tech' => [
            'Software Engineering', 'Data Science', 'Cyber Security', 'AI & Machine Learning', 
            'Robotics', 'Mechanical Engineering', 'Civil Engineering', 'Electrical Engineering', 
            'Chemical Engineering', 'Computer Science', 'Information Technology', 'Tech'
        ],
        'Healthcare' => [
            'Medicine', 'Pharmacy', 'Nursing', 'Public Health', 'Biology', 'Chemistry', 
            'Physics', 'Environmental Science', 'Healthcare', 'Healthy', 'Health'
        ],
        'Business' => [
            'Business Administration', 'Finance', 'Accounting', 'Economics', 
            'Human Resources', 'Supply Chain', 'Entrepreneurship', 'Business'
        ],
        'Creative & Media' => [
            'Graphic Design', 'UI/UX Design', 'Interior Design', 'Fashion Design', 
            'Photography', 'Film & Media', 'Journalism', 'Content Writing', 'Design', 'Media'
        ],
        'Law & Social Sciences' => [
            'Law', 'International Relations', 'Psychology', 'Sociology', 
            'Political Science', 'Education'
        ],
        'Marketing' => [
            'Digital Marketing', 'Public Relations', 'Sales', 'Event Management', 'Marketing'
        ]
    ];

    /**
     * Get the umbrella group for a specific category or faculty.
     */
    public static function getUmbrellaFor($field)
    {
        if (!$field) return null;

        foreach (self::$categoryUmbrellas as $umbrella => $fields) {
            foreach ($fields as $f) {
                if (stripos($field, $f) !== false || stripos($f, $field) !== false) {
                    return $umbrella;
                }
            }
        }

        return null;
    }

    /**
     * Get all related fields for an umbrella group or a specific field.
     */
    public static function getRelatedFields($fieldOrUmbrella)
    {
        if (!$fieldOrUmbrella) return [];

        // If it's already an umbrella name
        if (isset(self::$categoryUmbrellas[$fieldOrUmbrella])) {
            return self::$categoryUmbrellas[$fieldOrUmbrella];
        }

        // If it's a field, find its umbrella and return all fields in that umbrella
        $umbrella = self::getUmbrellaFor($fieldOrUmbrella);
        if ($umbrella) {
            return self::$categoryUmbrellas[$umbrella];
        }

        return [$fieldOrUmbrella];
    }
}
