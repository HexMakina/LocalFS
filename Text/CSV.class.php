<?php

namespace HexMakina\LocalFS\Text;

class CSV extends TextFile
{


  // T — Gets line from file pointer and parse for CSV fields
  // fgetcsv ( resource $handle [, int $length = 0 [, string $delimiter = "," [, string $enclosure = '"' [, string $escape = "\\" ]]]] ) : array

  // fputcsv ( resource $handle , array $fields [, string $delimiter = "," [, string $enclosure = '"' [, string $escape_char = "\\" ]]] ) : int
  /*
<?php

$list = array (
    array('aaa', 'bbb', 'ccc', 'dddd'),
    array('123', '456', '789'),
    array('"aaa"', '"bbb"')
);

$fp = fopen('file.csv', 'w');

foreach ($list as $fields) {
    fputcsv($fp, $fields);
}

fclose($fp);


*/
	function array()
	{
		return array_map('str_getcsv', parent::array());
	}


}
