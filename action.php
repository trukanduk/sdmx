<?php
	require_once('sdmx2\\SdmxData.php');
	require_once('sdmx2\\SdmxTableGenerator.php');

	/************************************************************************************************************************************************
		                                                                                                                                   ПЕРВЫЙ ШАГ
	 ************************************************************************************************************************************************/
	if ($_GET['act'] === 'get_file_list') {
		$ret = '';

		if (isset($_GET['dir']))
			$path = $_GET['dir'];
		else
			$path = 'sdmx2\\files';

		$dir = scandir($path);
		for ($i = 0; $i < count($dir); ++$i) {
			if ( is_dir($dir[$i]))
				continue;

			$ret .= "<div id='filelist_{$i}_div' class='filelist_element'>{$dir[$i]}</div>\n";
		}

		echo $ret;

	/************************************************************************************************************************************************
		                                                                                                                                   ВТОРОЙ ШАГ
	 ************************************************************************************************************************************************/
	} else if ($_GET['act'] === 'get_axes') {
		$filename = $_GET['file'];

		if ( ! file_exists($filename)) {
			sleep(4);
			die();
		}

		try {
			$sdmx = new SdmxData($filename, 'sdmx2\\.saved_axes.xml');
		} catch (Exception $e) {}
		
		if ( ! $sdmx) {
			sleep(4);
			die();
		}

		$ret = <<<TABLE
			<table id="tab_2_table">
				<tr>
					<td id="tab_2_table_header_unfixed_td" class="tab_2_table_header_td tab_2_table_td">
						Переменные оси:
					</td>
					<td></td>
				</tr>
TABLE;

		$axisInd = 0;
		foreach ($sdmx->GetDataSet()->GetUnfixedAxesIterator() as $axisId => $axis) {
			if ($axisInd == 0) {
				$inputsChecked = array(' checked', '');
			} else {
				$inputsChecked = array('', ' checked');
			}
			$ret .= <<<TR
				<tr class="tab_2_table_tr">
					<td class="tab_2_table_td tab_2_table_axisname_td">
						{$axis->GetName()}
					</td>
					<td id="tab_2_table_{$axisInd}_axisparam_td" class="tab_2_table_td tab_2_table_axisparam_td">
						<input type="radio" id="tab_2_table_{$axisInd}_axisparam_0_input" name="tab_2_table_{$axisInd}_axisparam_input"
							class="tab_2_table_axisparam_input" value="0"{$inputsChecked[0]} />
						Отложить по вертикали <br>
						<input type="radio" id="tab_2_table_{$axisInd}_axisparam_1_input" name="tab_2_table_{$axisInd}_axisparam_input"
							class="tab_2_table_axisparam_input" value="1"{$inputsChecked[1]} />
						Отложить по горизонтали
					</td>
				</tr>
TR;
			++$axisInd;
		}

		$ret .= <<<TR
				<tr>
					<td id="tab_2_table_header_fixed_td" class="tab_2_table_header_td tab_2_table_td">
						Постоянные оси:
					</td>
					<td></td>
				</tr>
TR;

		foreach ($sdmx->GetDataSet()->GetFixedAxesIterator() as $axisId => $axis) {
			$ret .= <<<TR
				<tr class="tab_2_table_tr">
					<td class="tab_2_table_td tab_2_table_axisname_td">
						{$axis->GetName()}
					</td>
					<td id="tab_2_table_{$axisInd}_axisparam_td" class="tab_2_table_td tab_2_table_axisparam_td">
						<input type="radio" id="tab_2_table_{$axisInd}_axisparam_2_input" name="tab_2_table_{$axisInd}_axisparam_input"
							class="tab_2_table_axisparam_input" value="2" checked/>
						Не отображать <br>
						<input type="radio" id="tab_2_table_{$axisInd}_axisparam_3_input" name="tab_2_table_{$axisInd}_axisparam_input"
							class="tab_2_table_axisparam_input" value="3" />
						Отобразить в заголовке <br>
						<input type="radio" id="tab_2_table_{$axisInd}_axisparam_0_input" name="tab_2_table_{$axisInd}_axisparam_input"
							class="tab_2_table_axisparam_input" value="0"/>
						Отложить по вертикали<br>
						<input type="radio" id="tab_2_table_{$axisInd}_axisparam_1_input" name="tab_2_table_{$axisInd}_axisparam_input"
							class="tab_2_table_axisparam_input" value="1" />
						Отложить по горизонтали
					</td>
				</tr>
TR;
			++$axisInd;
		}
		$ret .= "</table>";
		echo $ret;

	/************************************************************************************************************************************************
		                                                                                                                                   ТРЕТИЙ ШАГ
	 ************************************************************************************************************************************************/
	} else if ($_GET['act'] == 'get_table_') {
		$filename = $_GET['file'];

		if ( ! file_exists($filename)) {
			sleep(4);
			die();
		}

		try {
			$sdmx = new SdmxData($filename, 'sdmx2\\.saved_axes.xml');
		} catch (Exception $e) {}
		
		if ( ! $sdmx) {
			sleep(4);
			die();
		}

		$dataSet = $sdmx->GetDataSet();
		$tableText = '<table id="tab_3_table">';
		$headerText = strval($sdmx->GetDescription()->Indicator['name']);
		// Сформируем три массива: $idToInd = [$axisId => $ind] ($ind из GET-запроса) и два $xAxes, $yAxes: [$axisId] -- последовательность
		// использования осей
		// Так же необходим размер таблицы по обеим осям ($tableWidth и $tableHeight) -- произведения всех количеств значений по каждой оси
		$axisInd = 0;
		$idToInd = array();
		$xAxes = array();
		$yAxes = array();
		$tableWidth = 1;
		$tableHeight = 1;

		foreach ($dataSet->GetUnfixedAxesIterator() as $axisId => $axis) {
			if ( ! isset($_GET['axis'.$axisInd])) {
				sleep(4);
				die();
			}

			$idToInd[$axisId] = $axisInd;
			if ($_GET['axis'.$axisInd] == '0') {
				$yAxes[] = $axisId;
				$tableHeight *= $dataSet->GetValuesCount($axisId);
			} else {
				$xAxes[] = $axisId;
				$tableWidth *= $dataSet->GetValuesCount($axisId);
			}

			++$axisInd;	
		}

		// Для фиксированных осей с статусом "не использовать" также хочу переменную, где они будут свормированы,
		// чтобы запихнуть их в свойство alt ячеек
		$fixedAxesValues = '';
		foreach ($dataSet->GetFixedAxesIterator() as $axisId => $axis) {
			if (! isset($_GET['axis'.$axisInd])) {
				sleep(4);
				die();
			}

			$idToInd[$axisId] = $axisInd;
			if ($_GET['axis'.$axisInd] == '0') {
				$yAxes[] = $axisId;
				$tableHeight *= $dataSet->GetValuesCount($axisId);
			} else if ($_GET['axis'.$axisInd] == '1') {
				$xAxes[] = $axisId;
				$tableWidth *= $dataSet->getValuesCount($axisId);
			} else if ($_GET['axis'.$axisInd] == '2') {
				if ($fixedAxesValues != '')
					$fixedAxesValues .= ', ';
				
				$fixedAxesValues .= $dataSet->GetAxis($axisId)->GetValue($dataSet->GetFirstValue($axisId));
			} else if ($_GET['axis'.$axisInd] == '3') {
				$headerText .= ", " . $dataSet->GetAxis($axisId)->GetValue($dataSet->GetFirstValue($axisId));
			}

			++$axisInd;
		}
		
		// *******************************************
		// Генерация горизонтальной шапки

		// в переменной хранится, сколько раз надо продублировать все значения оси
		$currDublications = 1;
		for ($i = 0; $i < count($xAxes); ++$i) {
			$colspan = count($yAxes);
			$tableText .= <<<AXISNAME_TD
				<tr class="tab_3_table_header_tr">
					<td class="tab_3_table_header_axisname_td tab_3_table_header_td" colspan="{$colspan}">
						{$sdmx->GetAxis($xAxes[$i])->GetName()}:
					</td>
AXISNAME_TD;
			
			// индекс ячейки в строке
			$valueInd = 0;
			for ($j = 0; $j < $currDublications; ++$j) {
				foreach ($dataSet->GetValuesIterator($xAxes[$i]) as $value) {
					$colspan = $tableWidth/$currDublications/$dataSet->GetValuesCount($xAxes[$i]);
					$tableText .= <<<AXISVALUE_TD
						<td class="tab_3_table_header_axisvalue_td tab_3_table_header_td" colspan="{$colspan}">
							{$sdmx->GetAxis($xAxes[$i])->GetValue($value)}
						</td>
AXISVALUE_TD;
					++$valueInd;
				}
			}
			$tableText .= '</tr>';
			$currDublications *= $dataSet->GetValuesCount($xAxes[$i]);
		}
		// Частный случай, если по горизонтали не отложено ни одой оси:
		if (count($xAxes) == 0) {
			$colspan = count($yAxes);
			$tableText .= <<<HEADER_TD
				<tr class="tab_3_table_header_axisname_td">
					<td class="tab_3_table_header_axisname_td tab_3_table_header_td" colspan="{$colspan}"></td>
					<td class="tab_3_table_header_axisvalue_td tab_3_table_header_td">Значения:</td>
				</tr>
HEADER_TD;
		}

		// ************************************************
		// Генерация ячеек со значениями
		function generate_cells(ISdmxDataSet $dataSet, $xAxisIndex) {
			global $xAxes, $fixedAxesValues;
			if ($dataSet->GetUnfixedAxesCount() == 0) {
				return "<td class='tab_3_table_value_td' alt='{$fixedAxesValues}'>{$dataSet->GetFirstPoint()->GetValue()}</td>";
			} else {
				$ret = '';
				foreach ($dataSet->GetSlice($xAxes[$xAxisIndex]) as $subset) {
					$ret .= generate_cells($subset, $xAxisIndex + 1);
				}
				return $ret;
			}
		}

		// ***************************************************
		// Генерация всей таблицы, кроме шапки
		function generate_table(ISdmxDataSet $dataSet, $yAxisIndex, $currHeight, $isFirstCell) {
			global $xAxes, $yAxes;
			if (count($yAxes) == 0) {
				$ret = "<tr class='tab_3_table_value_tr'>
					<td class='tab_3_table_header_axisvalue_td tab_3_table_header_td'>Значения:</td>";
				$ret .= generate_cells($dataSet, 0);
				$ret .= "</tr>";
				return $ret;
			} else if (count($yAxes) == $yAxisIndex) {
				$ret = '';
				$ret .= generate_cells($dataSet, 0);
				$ret .= "</tr>";
				return $ret;
			} else {
				$ret = '';
				$isNextFirst = false;

				$rowspan = $currHeight/$dataSet->GetValuesCount($yAxes[$yAxisIndex]);
				foreach ($dataSet->GetSlice($yAxes[$yAxisIndex]) as $val => $subset) {
					if ($isNextFirst) {
						$ret .= "<tr class='tab_3_table_value_tr'>";
					}
					$ret .= "<td class='tab_3_table_header_axisvalue_td tab_3_table_header_td' rowspan='{$rowspan}'>{$dataSet->GetAxis($yAxes[$yAxisIndex])->GetValue($val)}</td>";
					$ret .= generate_table($subset, $yAxisIndex + 1, $rowspan, $isNextFirst);
					$isNextFirst = true;
				}
				return $ret;
			}
		}

		//generate_table($dataSet, $xAxes, $yAxes, 0, true, $tableText);
		$tableText .= generate_table($dataSet, 0, $tableHeight, false);
		$tableText .= '</table>';

		$ret = <<<RET
			<h3 id="tab_3_header">
				{$headerText}
			</h3>
			{$tableText}
RET;
		echo $ret;
	} else if ($_GET['act'] == 'get_table') {
		$filename = $_GET['file'];

		if ( ! file_exists($filename)) {
			sleep(4);
			die();
		}

		try {
			$sdmx = new SdmxData($filename, 'sdmx2\\.saved_axes.xml');
		} catch (Exception $e) {}
		
		if ( ! $sdmx) {
			sleep(4);
			die();
		}

		$dataSet = $sdmx->GetDataSet();
		$tableText = '<table id="tab_3_table">';
		$headerText = strval($sdmx->GetDescription()->Indicator['name']);
		// Сформируем три массива: $idToInd = [$axisId => $ind] ($ind из GET-запроса) и два $xAxes, $yAxes: [$axisId] -- последовательность
		// использования осей
		// Так же необходим размер таблицы по обеим осям ($tableWidth и $tableHeight) -- произведения всех количеств значений по каждой оси
		$axisInd = 0;
		$idToInd = array();
		$xAxes = array();
		$yAxes = array();
		$tableWidth = 1;
		$tableHeight = 1;

		foreach ($dataSet->GetUnfixedAxesIterator() as $axisId => $axis) {
			if ( ! isset($_GET['axis'.$axisInd])) {
				sleep(4);
				die();
			}

			$idToInd[$axisId] = $axisInd;
			if ($_GET['axis'.$axisInd] == '0') {
				$yAxes[] = $axisId;
				$tableHeight *= $dataSet->GetValuesCount($axisId);
			} else {
				$xAxes[] = $axisId;
				$tableWidth *= $dataSet->GetValuesCount($axisId);
			}

			++$axisInd;	
		}

		// Для фиксированных осей с статусом "не использовать" также хочу переменную, где они будут свормированы,
		// чтобы запихнуть их в свойство alt ячеек
		$fixedAxesValues = '';
		foreach ($dataSet->GetFixedAxesIterator() as $axisId => $axis) {
			if (! isset($_GET['axis'.$axisInd])) {
				sleep(4);
				die();
			}

			$idToInd[$axisId] = $axisInd;
			if ($_GET['axis'.$axisInd] == '0') {
				$yAxes[] = $axisId;
			} else if ($_GET['axis'.$axisInd] == '1') {
				$xAxes[] = $axisId;
			} else if ($_GET['axis'.$axisInd] == '2') {
				if ($fixedAxesValues != '')
					$fixedAxesValues .= ', ';
				
				$fixedAxesValues .= $dataSet->GetAxis($axisId)->GetValue($dataSet->GetFirstValue($axisId));
			} else if ($_GET['axis'.$axisInd] == '3') {
				$headerText .= ", " . $dataSet->GetAxis($axisId)->GetValue($dataSet->GetFirstValue($axisId));
			}

			++$axisInd;
		}

		$cellsClasses = array('tab_3_table_header_axisname_td tab_3_table_header_td',
			                  'tab_3_table_header_axisvalue_td tab_3_table_header_td',
			                  'tab_3_table_header_axisvalue_td tab_3_table_header_td',
			                  'tab_3_table_value_td');
		foreach (new SdmxTableGenerator($dataSet, $xAxes, $yAxes) as $cell) {
			$tableText .= '<tr>';
			for ($cell->rewind(); $cell->valid(); $cell->next()) {
				if ($cell->GetMergedUp() != 0 || $cell->GetMergedLeft() != 0)
					continue;
				$tableText .= <<<TD
					<td class='{$cellsClasses[$cell->GetType()]}'
					    rowspan='{$cell->GetMergedDown()}'
					    colspan='{$cell->GetMergedRight()}'>
							{$cell->GetValue()}
					</td>
TD;
			}
			$tableText .= '</tr>';
		}
		/*
		for ($yInd = 0; $yInd < $tableHeight + count($xAxes); ++$yInd) {
			$tableText .= "<tr class='tab_3_table_header_tr'>";

			if ($yInd < count($xAxes))
				$iter = new SdmxHeaderRowTableGenerator($dataSet, $xAxes, $yAxes, $yInd);
			else
				$iter = new SdmxRegularRowTableGenerator($dataSet, $xAxes, $yAxes, $yInd);

					    #rowspan="{$iter->GetMergedDown()}"
					    #colspan="{$iter->GetMergedRight()}">
			for ($iter->rewind(); $iter->valid(); $iter->next()) {
				if (0 && ($iter->GetMergedUp() != 0 || $iter->GetMergedLeft() != 0)) {
					continue;
				}
				$tableText .= <<<TD
					<td class="{$cellsClasses[$iter->GetType()]}">
						{$iter->GetValue()}
					</td>
TD;
			}
			if (0 && $yInd == 20)
				break;
			$tableText .= '</tr>';
		}
		*/
		$tableText .= '</table>';

		echo $tableText;
	}
?>
