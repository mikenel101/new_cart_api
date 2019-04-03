<?php

namespace MikesLumenBase\Utils;

use SplFileObject;
use MikesLumenApi\Exceptions\ValidatorException;
use Illuminate\Http\Request;

class CSVUtils
{

    const ENCODING_CHECK_LINES = 100;
    const SUBMIT_CHUNK_SIZE = 500;

    /**
     * Handle the csv file from @request
     */
    public static function uploadCsvData($headers, $csv, $filename, array $csvHeaderArray, $validateFunctionCallback, $storeFunctionCallback, $formatFunctionCallback)
    {
        $chunkSize = self::SUBMIT_CHUNK_SIZE;

        if (pathinfo($filename)['extension'] != "csv") {
            throw new ValidatorException(array('csv_extension' => trans('csv.message.need_csv_extension')));
        }

        $file = new SplFileObject($csv);
        // check the encoding of file, throw Exception if unrecongized
        $encoding = self::checkEncodingOfFirstNLines($file, self::ENCODING_CHECK_LINES);
        if (!$encoding) {
            throw new ValidatorException(array('unrecognized_encoding' => trans('csv.message.unrecognized_encoding')));
        }

        // change the encoding of contents in the imported file
        self::changeFileEncoding($csv, 'UTF-8', $encoding);
        $file = new SplFileObject($csv);
        $file->setFlags(SplFileObject::READ_CSV);

        // get the tranlated Header Array
        $translatedCsvHeaderArray = self::buildTranslateColumns($csvHeaderArray);

        // get csv headers info
        $csvHeaders = $file->current();
        // get $columnPaths defined in the $csvHeaderArray with the orders of $csvHeaders
        $columnPaths = array();
        foreach ($csvHeaders as $translatedCsvColumnName) {
            $columnPaths[] = $translatedCsvHeaderArray[$translatedCsvColumnName];
        }

        // validate csv data error
        $errorMessages = self::validateCsvData($file, $headers, $csvHeaderArray, $columnPaths, $chunkSize, $validateFunctionCallback, $formatFunctionCallback);
        if (count($errorMessages) > 0) {
            throw new ValidatorException($errorMessages);
        }

        // send the csv data after validate result is ok
        self::storeCsvData($file, $headers, $columnPaths, $chunkSize, $storeFunctionCallback, $formatFunctionCallback);

        return response('', 200);
    }

    public static function getUploadFileExtension(Request $request)
    {
        return $request->input('name');
    }

    /**
     * Store the data from import @file
     */
    private static function storeCsvData(SplFileObject $file, $headers, $columnPaths, $chunkSize, $storeFunctionCallback, $formatFunctionCallback)
    {
        // check file line count
        $file->seek($file->getSize());
        $lineCount = $file->key();
        if ($lineCount > 0) {
            $file->seek(1);
            $i = 0;
            $requestParamsChunk = array();
            do {
                $line = $file->current();

                $requestParams = array();
                for ($j = 0; $j < count($columnPaths); $j++) {
                    self::setArrayValueByPath($requestParams, $columnPaths[$j], $line[$j]);
                }

                // handle the special column
                $requestParamsChunk[] = call_user_func($formatFunctionCallback, $requestParams);
                if ($i >= $chunkSize) {
                    // store the data when got enough data
                    call_user_func($storeFunctionCallback, $headers, $requestParamsChunk);
                    $requestParamsChunk = array();
                    $i = 0;
                }

                $file->next();
                $i++;
            } while (!$file->eof());
            // store the last data
            if (count($requestParamsChunk) > 0) {
                call_user_func($storeFunctionCallback, $headers, $requestParamsChunk);
            }
        }
    }

    /**
     * Validate the Csv Data from import @file
     */
    private static function validateCsvData(SplFileObject $file, $headers, $csvHeaderArray, $columnPaths, $chunkSize, $validateFunctionCallback, $formatFunctionCallback)
    {
        // validate csv data in user_api
        $errorMessages = array();

        // check file line count
        $file->seek($file->getSize());
        $lineCount = $file->key();
        if ($lineCount > 0) {
            $requestParamsChunk = array();
            $file->seek(1);
            $i = 0;
            $index = 0;
            do {
                $line = $file->current();

                $requestParams = array();
                for ($j = 0; $j < count($columnPaths); $j++) {
                    self::setArrayValueByPath($requestParams, $columnPaths[$j], $line[$j]);
                }

                // handle the special column
                $requestParamsChunk[] = call_user_func($formatFunctionCallback, $requestParams);

                if ($i >= $chunkSize) {
                    // validate the data when got enough count
                    $result = call_user_func($validateFunctionCallback, $headers, $requestParamsChunk);
                    $errorMessages = array_merge($errorMessages, self::formatValidateResult($csvHeaderArray, $result, $index - $i));
                    $requestParamsChunk = array();
                    $i = 0;
                }

                $file->next();
                $i++;
                $index++;
            } while (!$file->eof());
            // validate the last data
            if (count($requestParamsChunk) > 0) {
                $result = call_user_func($validateFunctionCallback, $headers, $requestParamsChunk);
                $errorMessages += self::formatValidateResult($csvHeaderArray, $result, $index - $i);
            }
        }
        return $errorMessages;
    }

    /**
     * JP:
     * @dataの指定パスに値を設定する
     *
     * @data 処理したいデータ
     * @path 値を設定したい@dataのパス
     * @value 設定したい値
     *
     * EN:
     * set the value to the specified path of @data
     *
     * @data original data
     * @path the path of wanna setting the data to
     * @value value of wanna setting
     */
    public static function setArrayValueByPath(&$data, $path, $value)
    {
        $temp = &$data;
        foreach (explode('.', $path) as $key) {
            $temp = &$temp[$key];
        }
        $temp = $value;
        unset($temp);
    }

    /**
     * Translate the keys of @array
     */
    private static function buildTranslateColumns($array)
    {
        $tranlateKeyColumns = array();
        foreach ($array as $key => $value) {
            if ($key == "custom_field") {
                for ($i = 1; $i <= 20; $i++) {
                    $tranlateKeyColumns[trans("csv." . $key) . $i] = $value . '.' . "custom_field" . $i;
                }
            } else {
                $tranlateKeyColumns[trans("csv." . $key)] = $value;
            }
        }

        return $tranlateKeyColumns;
    }

    /**
     * Check the encoding of @file by first @n lines
     */
    private static function checkEncodingOfFirstNLines($file, $n)
    {
        $str = '';
        $i = 0;
        while (!$file->eof() && $i < $n) {
            $line = $file->current();
            $str .= $line;
            $file->next();
            $i++;
        }
        $encode = mb_detect_encoding($str, array('UTF-8', 'EUC-JP', 'SJIS'));

        return $encode;
    }

    /**
     * Change the encoding of specifed @filePath from @encodingFrom to @encodingTo
     */
    private static function changeFileEncoding($filePath, $encodingTo, $encodingFrom)
    {
        file_put_contents($filePath, mb_convert_encoding(file_get_contents($filePath), $encodingTo, $encodingFrom));
    }

    /**
     * Return the @headerKey(ex: customers.username) by @valuePath (ex: user.username) defined in @headerArray
     */
    private static function getCsvHeaderKeyByValuePath($headerArray, $valuePath)
    {
        if (false !== strpos($valuePath, 'custom_field')) {
            $headerKey = 'custom_field' . str_replace('custom_field.custom_field', '', $valuePath);
        } else {
            $headerKey = array_search($valuePath, $headerArray);
        }

        return $headerKey;
    }

    /**
     * Format the validate result from other service
     * Need specified @headerArray (ex: const CUSTOMER_CSV_HEADER_ARRAY)
     */
    private static function formatValidateResult($headerArray, $result, $baseIndex = 0)
    {
        $errorMessages = array();
        // set the real index for error messages
        if (count($result) > 0) {
            foreach ($result as $index => $msgBag) {
                $errorStr = '';
                foreach ($msgBag as $valuePath => $msgArray) {
                    $translatedHeaderKey = trans('csv.' . self::getCsvHeaderKeyByValuePath($headerArray, $valuePath));
                    $errorStr .= implode(str_replace($valuePath, $translatedHeaderKey, $msgArray));
                }
                $errorMessages[$baseIndex + (int)$index] = $errorStr;
            }
        }

        return $errorMessages;
    }
}
