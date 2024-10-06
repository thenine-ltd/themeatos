<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 10/07/2023
 * Time: 20:58
 *
 * @since 1.14.0
 */

namespace WPCCrawler\Objects\Arrays;

use Illuminate\Support\Arr;

/**
 * Sanitizes an array by returning only the values of specified paths, resulting in removing all the other values from
 * the array
 */
class ArraySanitizer {

    /** @var array */
    private $data;

    /** @var array<string[]> */
    private $desiredPaths;

    /**
     * @param array    $data         The data from which the paths will be retrieved.
     * @param string[] $desiredPaths The paths that are wanted from the data, in dot notation, where "*" can be used.
     * @since 1.14.0
     */
    public function __construct(array $data, array $desiredPaths) {
        $this->data = $data;
        $this->desiredPaths = array_map(function($path) {
            return explode('.', $path);
        }, $desiredPaths);
    }

    /**
     * @return array The array that contains only the values of the desired paths
     * @since 1.14.0
     */
    public function sanitize(): array {
        $results = [];
        $data = $this->getData();
        foreach($this->getDesiredPaths() as $desiredPath) {
            self::extractData($results, $data, $desiredPath);
        }

        return Arr::undot($results);
    }

    /*
     *
     */

    /**
     * @return array
     * @since 1.14.0
     */
    public function getData(): array {
        return $this->data;
    }

    /**
     * @return array<string[]>
     * @since 1.14.0
     */
    public function getDesiredPaths(): array {
        return $this->desiredPaths;
    }

    /*
     *
     */

    /**
     * @param array<string, mixed> $results    Flat array where keys are dot-notation paths of the values. This array
     *                                         will be populated with the results.
     * @param mixed                $data       The data from which the values will be extracted
     * @param string|string[]      $targetPath The dot-notation path of the target values
     * @param string               $pathPrefix This is used internally in the recursion. No need to set it.
     * @since 1.14.0
     */
    public static function extractData(array &$results, $data, $targetPath, string $pathPrefix = ''): void {
        // Retrieve only the specified keys from the array and create a result (a flat array) where the keys are
        //  the dot-notation paths for the values. By this way, we can use `Arr::undot()` to create the final array that
        //  contains only the desired values.
        $pathSegments = is_array($targetPath)
            ? $targetPath
            : explode('.', $targetPath);

        // Get the first segment and remove it from the segments
        $currentSegment = array_shift($pathSegments);

        // If the segment is a wildcard
        if ($currentSegment === '*') {
            // If the data is not an array, stop.
            if (!is_array($data)) {
                return;
            }

            // If this is the last path segment, directly add it to the results.
            if (!$pathSegments) {
                // If there is no path prefix, assign the data to the $results array directly, to avoid having an empty
                // key in the results array.
                if ($pathPrefix === '') {
                    $results = $data;

                } else {
                    $results[trim($pathPrefix, '.')] = $data;
                }

                return;
            }

            // The data is an array. Use each item as the target data and extract the remaining path segments from them
            // recursively.
            foreach($data as $targetPath => $target) {
                self::extractData($results, $target, $pathSegments, $pathPrefix.$targetPath.'.');
            }

            return;
        }

        // Get the value of the current path segment from the data.
        $targetData = is_array($data)
            ? ($data[$currentSegment] ?? null)
            : null;
        if ($targetData === null) {
            return;
        }

        // If there are no remaining segments, add this data to the results
        if (!$pathSegments) {
            $results[$pathPrefix.$currentSegment] = $targetData;

        } else {
            // There are remaining segments. Keep extracting the data.
            self::extractData($results, $targetData, $pathSegments, $pathPrefix.$currentSegment.'.');
        }
    }
}