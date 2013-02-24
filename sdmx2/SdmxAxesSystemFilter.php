<?php
	/**
	 * Описание фильтра системы координат
	 *
	 * @author Илья Уваренков <trukanduk@gmail.com>
	 * @package sdmx
	 * @version 1.0
	 */

	require_once('SdmxAxisFilters.php');

	/**
	 * Фильтр для всей системы координат
	 *
	 * @package sdmx
	 * @version 1.0
	 */
	class SdmxAxesSystemFilter {
		/**
		 * Фильтры по осям
		 * @var ISdmxAxisFilter[]
		 */
		protected $axisFilters = array();

		/**
		 * Фильтр оси по умолчанию
		 * @var ISdmxAxisFilter
		 */
		protected $defaultAxisFilter;

		/**
		 * Получение состояния значения
		 *
		 * Функция возврачает <var>true</var>, если данное значение должно содержаться в данной оси
		 * @param string $axisId идентификатор оси, по которой проверяется значение
		 * @param string $value Сфрое значение оси, которое надо проверить
		 * @return bool <var>true</var>, если значение должно содержаться.
		 */
		function IsAxisValueSifted($axisId, $value) {
			if (isset($this->axisFilters[$axisId])) {
				return $this->axisFilters[$axisId]->IsSifted($value);
			} else {
				return $this->defaultAxisFilter->IsSifted($value);
			}
		}

		/**
		 * Получение состояния точки
		 *
		 * @param SdmxDataPoint $point тестируемая точка
		 * @return bool <var>true</var>, если точка остаётся в множестве
		 */
		function IsSifted(SdmxDataPoint $point) {
			// var_dump($this);
			$ret = true;
			for ($it = $point->GetCoordinatesIterator(); $ret && $it->valid(); $it->next()) {
				if ($it->key() == 'Time') {
					//echo ($this->IsAxisValueSifted($it->key(), $it->current()->GetRawValue()) ? 'true' : 'false') . "\n";
				}
				$ret = $this->IsAxisValueSifted($it->key(), $it->current()->GetRawValue());
			}
			return $ret;
		}

		/**
		 * Получение фильтра оси
		 * @param string $axisId идентификатор интересуемой оси
		 * @return ISdmxAxisFilter Фильтр, который используется для этой оси (если нет в массиве, вернётся <var>$this->defaultAxisFilter</var>)
		 */
		function GetAxisFilter($axisId) {
			if (isset($this->axesFilters[$axisId]))
				return $this->axesFilters[$axisId];
			else
				return $this->defaultAxisFilter;
		}

		/**
		 * Установка фильтра оси
		 * @param string $axisId идентификатор оси
		 * @param ISdmxAxisFilter $filter устанавливаемый фильтр
		 * @return SdmxAxiesSystemFilter объект-хозяин метода
		 */
		function SetAxisFilter($axisId, ISdmxAxisFilter $filter) {
			$this->axisFilters[$axisId] = $filter;
			if ($axisId == 'Time') {
				// var_dump($this);
			}
			return $this;
		}

		/**
		 * Удаление фильтра оси
		 * @param string $axisId идентификатор оси, фильтр которой будет удалён
		 * @return SdmxAxesSystemFilter объект-хозяин метода
		 */
		function UnsetAxisFilter($axisId) {
			unset($this->axisFilters[$axisId]);
			return $this;
		}

		/**
		 * Получение итератора на массив фильтров осей
		 * @return Iterator
		 */
		function GetAxisFiltersIterator() {
			return new ArrayIterator($this->axisFilters);
		}

		/**
		 * Получение фильтра по умолчанию
		 *
		 * Фильтр по умолчанию используется, когда для какой-то оси не был задан фильтр
		 * @return ISdmxAxisFilter фильтр по умолчанию
		 */
		function GetDefaultAxisFilter() {
			return $this->defaultAxisFilter;
		}

		/**
		 * Установка фильтра по умолчанию
		 * @param ISdmxAxisFilter $filter Фильтр, который будет установлен как фильтр по умолчанию
		 * @return SdmxAxesSystemFilter объект-хозяин метода
		 */
		function SetDefaultAxisFilter(ISdmxAxisFilter $filter) {
			$this->defaultAxisFilter = $filter;
			return $this;
		}

		/**
		 * Конструктор
		 * @param ISdmxAxisFilter[] $axisFilters фильтры осей
		 * @param ISdmxAxisFilter $defaultFilter фильтр по умолчанию. По умолчанию -- <var>SdmxAxisFilter::All()</var>
		 */
		function __construct($axisFilters = array(), $defaultFilter = null) {
			foreach ($axisFilters as $axisId => $filter) {
				if (is_a($filter, 'ISdmxAxisFilter'))
					$this->axisFilters[$axisId] = $filter;
			}

			if (is_a($defaultFilter, 'ISdmxAxisFilter'))
				$this->defaultAxisFilter = $defaultFilter;
			else
				$this->defaultAxisFilter = SdmxAxisFilter::All();
		}
	}
?>
