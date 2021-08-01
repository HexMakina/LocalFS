<?php

namespace HexMakina\LocalFS\Text;

class CSV extends TextFile
{
	const LIST_ANORMAL_SEPARATOR_REGEX = '/\r\n|\n|\r/';

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

  //TODO WTF is this doing here ??
  public static function collection_of_models_to_csv($collection, $model, $output)
  {
    if($output === 'php://output')
    {
      $stream = "$output";
      header('Content-Description: File Transfer');
      header('Content-Type: application/octet-stream');
      header('Content-Disposition: attachment; filename=export_'.date('Ymd_Hi').".csv");
      header('Content-Transfer-Encoding: binary');
      header('Expires: 0');
      header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
      header('Pragma: public');
    }
    else
    {
      $stream = $output;
    }

    $stream = fopen($stream, "w");
    // csv header
    fputcsv($stream, array_keys($model->fields())); // CSV headers


    foreach($collection as $model)
    {
      $model = $model->export();
      foreach($model as $key => $value){
        $model[$key] = preg_replace(Model::LIST_ANORMAL_SEPARATOR_REGEX, '|', $value);
      }
      fputcsv($stream, $model);
    }
    fclose($stream);
    exit();
  }

}
