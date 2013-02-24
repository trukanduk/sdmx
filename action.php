<?php
	require_once('sdmx2/SdmxAxis.php');
	require_once('sdmx2/SdmxAxisFilters.php');
	require_once('sdmx2/SdmxAxesSystemFilter.php');
	require_once('sdmx2/SdmxData.php');
	require_once('sdmx2/SdmxTableGenerator.php');

	/**
	 * Значение пути до сохранённых осей
	 * @var string
	 */
	$defaultAxesPath = 'sdmx2/.saved_axes.xml';

	function GetFilesList($dir) {
		$ret = array();
		if ( ! file_exists($dir) || ! is_dir($dir)) {
			$ret['errno'] = 2;
			$ret['errmsg'] = "There isn't directory {$dir}";
			return $ret;
		}

		$files = scandir($dir);

		if ($files === false) {
			$ret['errno'] = 2;
			$ret['errmsg'] = "Cannot open directory {$dir}";
			return $ret;
		}

		$ret['errno'] = 0;
		$ret['errmsg'] = 'Success';

		$ret['count'] = 0;
		$ret['files'] = array();
		foreach ($files as $file) {
			if (is_dir($file))
				continue;

			$ret['count']++;
			$ret['files'][] = $file;
		}

		return $ret;
	}

	/**
	 * Получение осей файла
	 *
	 * Парсит файл $filename и возвращает массив с информациях об осях в формате:
	 * <code>
	 * [errno: $error_code, // ноль -- success
	 *  errmsg: $error_message, // сообщение при ошибке
	 *  // далее -- только если errno == 0
	 *  axes: [count: $axes_count, // количество осей
	 *         0: [id: $axis_id, // идентификатор оси
	 *             name: $axis_name, // имя оси (на человеческом языке)
	 *             count: $axis_values_count, // количество значений оси
	 *             0: [raw: $raw_value, // сырое значение
	 *                 value: $value // нормальное человеческое значение
	 *                ]
	 *             1: ...
	 *            ]
	 *         1: ...
	 *        ]
	 * ]
	 * </code>
	 * @param string $file файл с sdmx
	 * @return mixed[] Результат операции
	 */
	function ParseAxes($file) {
		global $defaultAxesPath;
		// результат
		$ret = array();

		// открытие файла
		$sdmx = new SdmxData($file, $defaultAxesPath);

		// Ещё одна проверка -- файл мог не открыться
		if ( ! $sdmx) {
			$ret['errno'] = 1;
			$ret['errmsg'] = "Cannot open sdmx file {$file}";
			return $ret;
		}

		// Снимем ошибку -- её теперь не будет
		$ret['errno'] = 0;
		$ret['errmsg'] = 'Success';
		$ret['axes'] = array();

		// Обработаем каждую ось
		$i = 0; // индекс очередной оси
		foreach ($sdmx->GetAxesIterator() as $axisId => $axis) {
			// инфа об оси
			$ret['axes'][$i] = array('id' => $axisId,
				                     'name' => $axis->GetName(),
				                     'count' => $sdmx->GetDataSet()->GetAxisValuesCount($axisId));
			// значения оси в формате {raw: $rawValue, value: $value}
			foreach ($sdmx->GetDataSet()->GetAxesValuesIterator($axisId) as $ind => $rawValue)
				$ret['axes'][$i][$ind] = array('raw' => $rawValue, 'value' => $axis->GetValue($rawValue));

			++$i;
		}
		// количество осей
		$ret['axes']['count'] = $i;

		return $ret;
	}

	/**
	 * Генерация таблицы
	 *
	 * Парсит файл и генерирует таблицу.
	 * Информация об осях (<var>$axesInfo</var>) должна иметь следующий вид:
	 * <code>
	 * [0: [id: $axis_id, // идентификатор оси
	 *      values: [0: [raw: $raw_value, // очередное сырое значение
	 *                   selected: $is_selected // bool -- следует ли его включать в таблицу
	 *                  ]
	 *               1: ...
	 *              ]
	 *     ]
	 * 1: ...
	 * ]
	 * </code>
	 * Увеличение информации не возбраняется, но перечисленные значения будут использоваться

	 * @param string $filename имя sdmx-файла
	 * @param mixed[] $axesInfo информация об осях
	 * @param string[] $xAxes идентификаторы осей, которые должны быть отложены по горизонтали (шапка сверху)
	 * @param string[] $yAxes идентификаторы осей, которые должны быть отложены по вертикали (шапка слева)
	 * @param string[] $headerAxes идентификаторы осей, которые должны быть выведены в заголовок (только статические!)
	 * @return mixed[] Результат 
	 */
	function MakeTable($filename, $axesInfo, $xAxes, $yAxes, $headerAxes) {
		global $defaultAxesPath;

		// результат
		$ret = array();

		// Фильтр для файла
		$filter = new SdmxAxesSystemFilter();

		// Пройдём по всем осям
		foreach ($axesInfo as &$axisInfo) {
			// содерём массив значений, которых не должно быть в таблице
			$filter_values = array();
			foreach ($axisInfo['values'] as $axisValue) {
				if ( ! $axisValue['selected'])
					$filter_values[] = $axisValue['raw'];
			}
			// добавим в фильтр
			$filter->SetAxisFilter($axisInfo['id'], SdmxAxisFilter::Except($filter_values));
		}

		// открытие файла
		$sdmx = new SdmxData($filename, $defaultAxesPath, $filter);

		// Определим, не произошло ли ошибки
		if ( ! $sdmx) {
			$ret['errno'] = 1;
			$ret['errmsg'] = 'Cannot open sdmx file {$filename}';
			return $ret;
		}

		// Теперь ошибок не предвидется
		$ret['errno'] = 0;
		$ret['errmsg'] = 'Success';

		// сгенерируем таблицу как двумерный массив
		$table = array();
		$tableGenerator = new SdmxTableGenerator($sdmx->GetDataSet(), $xAxes, $yAxes);
		foreach (new SdmxTableGenerator($sdmx->GetDataSet(), $xAxes, $yAxes) as $rowIter) {
			// строка
			$row = array();
			for ($rowIter->rewind(); $rowIter->valid(); $rowIter->next()) {
				// если эта клетка не верхняя левая в "блоке", не будем её добавлять
				if ($rowIter->GetMergedUp() != 0 || $rowIter->GetMergedLeft() != 0) {
					continue;
				}

				// ячейка
				$row[] = array('value' => $rowIter->GetValue(),
					           'type' => $rowIter->GetType(),
					           'rowspan' => $rowIter->GetMergedDown(),
					           'colspan' => $rowIter->GetMergedRight());
			}
			// добавим строку в таблицу
			$table[] = $row;
		}
		$ret['table'] = $table;

		$ret['header'] = strval($sdmx->GetDescription()->Indicator['name']);
		foreach ($headerAxes as $axisId) {
			$ret['header'] .= ", {$sdmx->GetDataSet()->GetFixedAxisValue($axisId)}";
		}

		return $ret;
	}

	define(ACTION_GETTING_FILELIST, 0);
	define(ACTION_GETTING_AXES, 1);
	define(ACTION_GETTING_TABLE, 2);

	if ($_GET['act'] == ACTION_GETTING_FILELIST) {
		if (isset($_POST['dir'])) {
			echo json_encode(GetFilesList($_POST['dir']));
		}
	} elseif ($_GET['act'] == ACTION_GETTING_AXES) {
		if (isset($_POST['filename'])) {
			echo json_encode(ParseAxes($_POST['filename']));
		}
	} elseif ($_GET['act'] == ACTION_GETTING_TABLE) {
		if (isset($_POST['filename']) && isset($_POST['axes']) && isset($_POST['x_axes']) && isset($_POST['y_axes']) && isset($_POST['header_axes'])) {
			echo json_encode(MakeTable($_POST['filename'],
				                       json_decode($_POST['axes'], true),
				                       json_decode($_POST['x_axes'], true),
				                       json_decode($_POST['y_axes'], true),
				                       json_decode($_POST['header_axes'], true)));
		}
	}
?>
