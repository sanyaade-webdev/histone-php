<?php
/**
 *    Copyright 2012 MegaFon
 *
 *   Licensed under the Apache License, Version 2.0 (the "License");
 *   you may not use this file except in compliance with the License.
 *   You may obtain a copy of the License at
 *
 *       http://www.apache.org/licenses/LICENSE-2.0
 *
 *   Unless required by applicable law or agreed to in writing, software
 *   distributed under the License is distributed on an "AS IS" BASIS,
 *   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *   See the License for the specific language governing permissions and
 *    limitations under the License.
 */

$WORK_DIR = implode('/', explode('/', str_replace('\\', '/', __DIR__), -2));

ini_set('log_errors', 'on');
ini_set('error_log', $WORK_DIR . '/target/php_errors.txt');

$TEST_CASES_XML_FOLDER = '/generated/test-cases-xml';


/* load xml-describe tests for parser */

$filename = $WORK_DIR . $TEST_CASES_XML_FOLDER . '/parser/cases.json';
$f = fopen($filename, 'rb');
if ($f) {
	$xmlTestArray = array();
	$fileList = json_decode(fread($f, filesize($filename)));
	if ($fileList && is_array($fileList)) {
		$fstr = '';
		foreach ($fileList as $fileTest) {
			try {
				$testFileContent = file_get_contents($WORK_DIR . $TEST_CASES_XML_FOLDER . '/parser/' . $fileTest);
				$testFileContent = preg_replace('/<!--.*-->/Us', '', $testFileContent);
				$suites = array();
				if (preg_match_all('/<suite\s.*>(.*)<\/suite\s*>/Uis', $testFileContent, $suites)) {
					if (isset($suites[1]) && is_array($suites[1])) {
						foreach ($suites[1] as $suite) {
							$cases = array();
							if (preg_match_all('/<case\s*>(.*)<\/case\s*>/Uis', $suite, $cases)) {
								if (isset($cases[1]) && is_array($cases[1])) {
									foreach ($cases[1] as $key => $case) {
										$input = $expected = $exception = null;
										$input = preg_replace('/^(.*input>)(.*)(<\/input.*)$/Uis', "$2", $case);
										$input = str_replace('&#xD;', "\n", $input); // Хак для передачи параметра в Data Provider
										if (preg_match('/<\/exception/', $case))
											$exception = preg_replace('/(.*exception.*>)(.*)(<\/exception.*)$/Uis', "$2", $case);
										elseif (preg_match('/<\/expected/', $case))
											$expected = preg_replace('/(.*expected.*>)(.*)(<\/expected.*)$/Uis', "$2", $case);
										$fstr.= 'array(';
										$fstr.='urldecode(\'' . urlencode($input) . '\'),';
										$fstr.='urldecode(\'' . urlencode($expected) . '\'),';
										$fstr.='urldecode(\'' . urlencode($exception) . '\'),';
										$fstr.='),';
									}
								}
							}
						}
					}
				}
			} catch (Exception $e) {
				throw new Exception('badTestFile');
			}
		}// end foreach 
		$filename = $WORK_DIR . '/generated/generated-tests/ParserAcceptanceTest.php';
		if (file_exists($filename)) {
			$origin = file_get_contents($filename);
			$result = preg_replace('|/\*\s*module_start\s*\*/(.*)/\*\s*module_end\s*\*/|Uis', '/*module_start*/' . $fstr . '/*module_end*/', $origin);
			$f = fopen($filename, 'wb');
			if ($f) {
				fwrite($f, $result);
				fclose($f);
				return;
			}
		}
	}
}

throw new Exception('badTestFile');
?>
