<?php
	/**
	 * Описание интерфейса <var>ISdmxDataSet</var> и сопутствующее
	 *
	 * @author Илья Уваренков <trukanduk@gmail.com>
	 * @package sdmx
	 * @version 2.0
	 */

	require_once('SdmxAxesSystemFilter.php');
	require_once('SdmxAxis.php');
	require_once('SdmxDataPoint.php');

	/**
	 * Интерфейс для многомерных массивов данных
	 *
	 * В идеале предполагается несколько реализаций,
	 * возможно, соптимизированных для разных типов запросов
	 *
	 * @package sdmx
	 * @version 2.0
	 */
	interface ISdmxDataSet {
		/**
		 * Получение оси
		 *
		 * @param string $axisId Идентификатор требуемой оси
		 * @param mixed $default значение по умолчанию -- в случае отсутствия искомой оси
		 * @return mixed искомая ось (<var>SdmxAxis</var>) или <var>$default</var>.
		 */
		function GetAxis($axisId, $default = false);

		/**
		 * Добавление новой оси
		 *
		 * Сначала в объект должны загоняться оси, а потом уже точки. Если добавляется ось к набору точек,
		 * то вываливается исключение
		 *
		 * @throws Exception Если в объекте же содержались точки
		 * @param SdmxAxis $axis новая ось
		 * @return ISdmxDataSet объект-хозяин объекта
		 */
		function AddAxis(SdmxAxis $axis);

		/**
		 * Получение итератора на массив осей
		 *
		 * @return Iterator итератор на массив осей
		 */
		function GetAxesIterator();

		/**
		 * Получение количества осей
		 *
		 * @return int Количество осей
		 */
		function GetAxesCount();

		/**
		 * Получение количества фиксированных осей
		 *
		 * @return int количество фиксированных осей множества
		 */ 
		function GetFixedAxesCount();

		/**
		 * Получение количества нефиксированных осей
		 *
		 * @return int количество нефиксированных осей множества
		 */ 
		function GetUnfixedAxesCount();

		/**
		 * Фиксированна ли ось
		 *
		 * Фиксированная ось -- это ось с одним значением.
		 *
		 * @param string $axisId идентификатор интересуемой оси
		 * @param mixed $default значение, которое будет возвращено в случае отсутствия оси
		 * @return mixed В случае наличия оси -- <var>bool</var> или <var>$default</var> в случае её отсутствия
		 */
		function IsAxisFixed($axisId, $default = false);

		/**
		 * Получение итератора на массив фиксированных осей
		 *
		 * @return Iterator итератор на фиксированные оси
		 */
		function GetFixedAxesIterator();

		/**
		 * Получение итератора на массив нефиксированных осей
		 *
		 * @return Iterator итератор на нефиксированные оси
		 */
		function GetUnfixedAxesIterator();

		/**
		 * Получение фильтра, используемого в множестве
		 *
		 * @return SdmxAxesSystemFilter фильтр множества
		 */
		function GetFilter();

		/**
		 * Установка нового фильтра множества
		 *
		 * Звпрещено устанавливать фильтр на непустое множество!
		 * @throws Exception в случае попытки отфильтровать непустое множествоы
		 * @param SdmxAxesSystemFilter $filter новый фильтр множества
		 * @return ISdmxDataSet изменённое множество-хозяин метода
		 */
		function SetFilter(SdmxAxesSystemFilter $filter);

		/**
		 * Фильтрация множества в новое множество
		 * 
		 * Копирует множество и применяет к нему данный фильтр
		 * @param SdmxAxesSystemFilter $filter Новый фильтр
		 * @return ISdmxDataSet новое множество, полученное путём фильтрации множества-хозяина метода
		 */
		function CopyWithFilter(SdmxAxesSystemFilter $filter);

		/**
		 * Получение итератора на массив значений оси
		 *
		 * Список значений какой-то оси в срезе может отличаться от полного.
		 * Функция возвращает итератор на массив со значениями (сырыми) оси конкретно этого объекта
		 *
		 * @param string $axisId идентификатор оси
		 * @param mixed $defаult Значение, которое вернётся в случае отсутствия оси
		 * @return Iterator итератор на массив со значениями оси
		 */
		function GetAxesValuesIterator($axisId, $default = false);

		/**
		 * Получение значения фиксированной оси
		 *
		 * Функция возвращает единственное значение фиксиованной оси
		 *
		 * @param string $axisId Идентификатор оси, значение которой необходимо вернуть
		 * @param mixed $default Значение, которое будет возвращено в случае отсутствия такой фиксированной оси или если множество пустое
		 * @return mixed первое сырое значение оси (<var>string</var>) или <var>$default</var>, если она не была найдена
		 */
		function GetFixedAxisValue($axisId, $default = false);

		/**
		 * Добавляет значение оси
		 *
		 * Следует отметить, что значение перед непосредственным добавлением проверяется в фильтре
		 * @param string $axisId идентификатор оси
		 * @param string $value добавляемое значение
		 * @return ISdmxDataSet объект-хозяин метода
		 */
		function AddAxisValue($axisId, $value);

		/**
		 * Получение значения оси по индексу
		 *
		 * @param string $axisId идентификатор оси
		 * @param int $ind индекс значения
		 * @param mixed $default значение, которое будет возвращено в случае ошибки
		 * @return mixed "сырое" значение оси или <var>$default</var> в случае ошибки
		 */
		function GetAxisValueByIndex($axisId, $ind, $default = false);

		/**
		 * Получение количества значений оси
		 *
		 * @param string $axisId идентификатор оси
		 * @param mixed $default значение, которое вернётся при отсутствии оси
		 * @return mixed Количество значений (<var>int</var>) или <var>$default</var> в случае отсутствия таковой
		 */
		function GetAxisValuesCount($axisId, $default = false);

		/**
		 * Добавление точки
		 *
		 * Все точки должны загоняться после добавления всех осей, а также все точки должны иметь коодринаты по всем осям
		 * DataSet'а (и, соответственно, только по ним).
		 *
		 * @throws Exception Если что-то не так с осями
		 * @param SdmxDataPoint $point дабавляемая точка
		 * @return ISdmxDataSet объект-хозяин метода
		 */
		function AddPoint(SdmxDataPoint $point);

		/**
		 * Сортирует точки и возвращает итератор
		 * 
		 * Сортирует точки в множестве. Если массив <var>$axesOrder</var> задан, то будет задан приоритет осей при сортировке
		 * (т.е. сначала будет сравниваться ось, стоящая раньше в массиве, при равенстве -- далее). Если не задан, то он будет
		 * сформирован сам в соответствии с последовательностью добавления точек в объект
		 * При взятии срезов гарантируется, что сортироваться подмножества будут так же, как отсортировано исходное множество
		 *
		 * @param string[] $axesOrder массив с приоритетами осей при сравнении (или <var>null</var>, чтобы использовать очерёдность добавления осей)
		 * @return ISdmxDataSet Отсортированное множество точек
		 */
		//function SortPoints($axesOrder = null);

		/**
		 * Получение итератора на массив точек
		 *
		 * Точки будут находиться в неопределённом порядке. Чтобы получить итератор на отсортированный массив следует
		 * использовать метод SortPoints
		 *
		 * @return Iterator итератор на множество точек
		 */
		function GetPointsIterator();

		/**
		 * Получение первой точки множества
		 *
		 * Функция возвращает первую точку множества. Имеет смысл, когда она единственная
		 *
		 * @param mixed $default значение, которое будет возвращено в случае отсутствия точек в множестве
		 * @return SdmxDataPoint первая точка множества (или <var>$default</var>, если множество пусто)
		 */
		function GetFirstPoint($default = false);

		/**
		 * Получение точки множества по координатам
		 *
		 * Ищет и возвращает точку с заданными координатами
		 * Набор координат задаётся либо в виде <var>SdmxDataPoint</var> с координатами, либо в виде
		 * массива в двух возможных вариантах: <var>[$axisId => $value, $axisId => $valueInd, $sdmxCoordinate]</var>
		 * Индекс должен быть строго int'ом, значение -- строго строкой
		 * Должны быть все оси, иначе ничего не будет возвращено!
		 * @param mixed $coordinates Координаты искомой точки
		 * @param mixed $default Значение, которое будет возвращено, если такой точки нет
		 */
		function GetPoint($coordinates, $default = false);
		/**
		 * Очистка множества
		 *
		 * Удаляет все точки и все оси из множества (но если есть какие-то специфичные параметры, то они остаются)
		 *
		 * @return ISdmxDataSet объект-хозяин метода
		 */
		function Clear();

		function __DebugPrintAxes($printValues = true);
		function __DebugPrintPoints();
		function __DebugPrint();
	}
?>
