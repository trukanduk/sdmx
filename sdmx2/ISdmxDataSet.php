<?php
	/**
	 * Файл содержит описание интерфейса <var>ISdmxDataSet</var>
	 *
	 * @todo split, срезы и прочее и прочее.
	 *
	 * @author Илья Уваренков <trukanduk@gmail.com>
	 * @package sdmx
	 * @version 0.1a
	 */

	require_once('SdmxAxis.php');
	require_once('SdmxDataPoint.php');

	/**
	 * Интерфейс для многомерных массивов данных
	 *
	 * Пока что нужен только для поддержки архитектуры во время дебага!
	 * Не описана полная функциональность, в идеале предполагается несколько реализаций,
	 * возможно, соптимизированных для разных типов запросов
	 *
	 * @package sdmx
	 * @version 0.1a
	 */
	interface ISdmxDataSet {
		function AddAxis(SdmxAxis $axis);
		function AddDataPoint(SdmxDataPoint $point);
		function __DebugPrint();
	}
?>
