<?php
	/**
	 * Описание классов <var>SdmxTableRowGenerator</var>
	 *
	 * @author Илья Уваренков <trukanduk@gmail.com>
	 * @package sdmx
	 * @version 2.0
	 */

	require_once('ISdmxTableRowGenerator.php');
	require_once('SdmxTableGenerator.php');

	require_once('SdmxTableEdgeCellsGenerator.php');
	require_once('SdmxTableXHeaderGenerator.php');
	require_once('SdmxTableYHeaderGenerator.php');
	require_once('SdmxTableRegularCellsGenerator.php');


	/**
	 * Генератор строки таблицы
	 *
	 * Итератор по ячейкам таблицы, находищимся в одной строке
	 * @package sdmx
	 * @version 2.0
	 */
	class SdmxTableRowGenerator implements ISdmxTableRowGenerator {
		/**
		 * Внутренний итератор
		 *
		 * По сути у нас есть четыре вида итераторов: угловая ячейка (сверху-слева),
		 * шапка сверху, шапка слева и ячейки со значениями
		 *
		 * @var SdmxTableRowInternalGenerator
		 */
		protected $internalGenerator;

		/**
		 * Первый итератор - по ячейкам слева (в шапке слева или в углу)
		 * @var int
		 */
		const FIRST_PART_ITERATOR = 0;

		/**
		 * Второй итераторо - по ячейкам справа (в шапке сверху или в ячейках со значениями)
		 * @var int
		 */
		const SECOND_PART_ITERATOR = 1;

		/**
		 * Инициализация итератора
		 * @param int $type тип итератора -- FIRST_PART_ITERATOR | SECONT_PART_ITERATOR
		 * @return SdmxTableRowGenerator объект-хозяин метода
		 */
		protected function InitInternalIterator($type) {
			// по умолчанию делаем первый тип
			if ($type == self::FIRST_PART_ITERATOR || $type != self::SECOND_PART_ITERATOR) {
				if ($this->parentGenerator->GetYInd() < $this->parentGenerator->GetHeaderRowsCount())
					$this->internalGenerator = new SdmxTableEdgeCellsGenerator($this->parentGenerator);
				else
					$this->internalGenerator = new SdmxTableYHeaderGenerator($this->parentGenerator);
			} else {
				if ($this->parentGenerator->GetYInd() < $this->parentGenerator->GetHeaderRowsCount())
					$this->internalGenerator = new SdmxTableXHeaderGenerator($this->parentGenerator);
				else
					$this->internalGenerator = new SdmxTableRegularCellsGenerator($this->parentGenerator);
			}
		}

		/**
		 * Родительский итератор
		 * @var SdmxTableGenerator
		 */ 
		protected $parentGenerator;

		/**
		 * Получение индекса ячейки в строке (с учётом шапок)
		 * @return int индекс ячейки в строке
		 */
		function GetXInd() {
			return $this->internalGenerator->GetXInd();
		}

		/**
		 * Получение индекса строки в таблице (с учётом шапок)
		 * @return int индекс строки в таблице
		 */
		function GetYInd() {
			return $this->parentGenerator->GetYInd();
		}

		/**
		 * Получение количества объединённых сверху ячеек
		 *
		 * Ячейки могут быть объединены. Функция возвращает число ячеек, с которыми объединена данная
		 * ячейка сверху (т.е. сколько ячеек над текущей объединено с ней)
		 * @return int количество ячеек сверху
		 */
		function GetMergedUp() {
			return $this->internalGenerator->GetMergedUp();
		}

		/**
		 * Получение количества объединённых снизу ячеек, учитывая текущую
		 *
		 * Ячейки могут быть объединены. Функция возвращает число ячеек, с которыми объединена данная
		 * ячейка снизу, включая её саму (т.е. сколько ячеек под текущей объединено с ней + она сама)
		 * @return int количество ячеек снизу
		 */
		function GetMergedDown() {
			return $this->internalGenerator->GetMergedDown();
		}

		/**
		 * Получение количества объединённых слева ячеек
		 *
		 * Ячейки могут быть объединены. Функция возвращает число ячеек, с которыми объединена данная
		 * ячейка слева (т.е. сколько ячеек слева от текущей объединено с ней)
		 * @return int количество ячеек слева
		 */
		function GetMergedLeft() {
			return $this->internalGenerator->GetMergedLeft();
		}

		/**
		 * Получение количества объединённых справа ячеек, учитывая текущую
		 *
		 * Ячейки могут быть объединены. Функция возвращает число ячеек, с которыми объединена данная
		 * ячейка справа (т.е. сколько ячеек справа от текущей объединено с ней + она сама)
		 * @return int количество ячеек справа
		 */
		function GetMergedRight() {
			return $this->internalGenerator->GetMergedRight();
		}

		/**
		 * Получение значения ячейки
		 * @return string значение, которое может быть записано в ячейку
		 */
		function GetValue() {
			return $this->internalGenerator->GetValue();
		}

		/**
		 * Получение объекта ячейки
		 *
		 * Это -- "сырое значение" для ячейки. В случае заголовков это null (в левом верхнем углу таблицы) или <var>SdmxCoordinate</var>,
		 * в случае ячейки со значением -- <var>SdmxDataPoint</var>
		 * @return mixed null, SdmxCoordinate или SdmxDataPoint
		 */
		function GetObject() {
			return $this->internalGenerator->GetObject();
		}

		/**
		 * Тип ячейки - угловой заголовок (левый верхний угол таблицы)
		 * @var int
		 */
		const TABLE_CELL_TYPE_EDGE_HEADER = 0;
		/**
		 * Тип ячейки - шапка сверху таблицы
		 * @var int
		 */
		const TABLE_CELL_TYPE_X_HEADER = 1;
		/**
		 * Тип ячейки - шапка слева таблицы
		 */
		const TABLE_CELL_TYPE_Y_HEADER = 2;
		/**
		 * Тип ячейки - ячейка со значением
		 */
		const TABLE_CELL_TYPE_REGULAR_CELL = 3;

		/**
		 * Получение типа ячейки
		 * @return int { TABLE_CELL_TYPE_EDGE_HEADER | TABLE_CELL_TYPE_X_HEADER | TABLE_CELL_TYPE_Y_HEADER | TABLE_CELL_TYPE_REGULAR_CELL }
		 */
		function GetType() {
			return $this->internalGenerator->GetType();
		}

		/**
		 * Следующая ячейка
		 * @return void
		 */
		function next() {
			if ( ! $this->internalGenerator->valid())
				return;

			$this->internalGenerator->next();
			if ( ! $this->internalGenerator->valid() && ($this->internalGenerator->GetType() == self::TABLE_CELL_TYPE_EDGE_HEADER ||
				                                         $this->internalGenerator->GetType() == self::TABLE_CELL_TYPE_Y_HEADER))
				$this->InitInternalIterator(self::SECOND_PART_ITERATOR);
		}

		/**
		 * Сброс итератора
		 * @return void
		 */
		function rewind() {
			$this->InitInternalIterator(self::FIRST_PART_ITERATOR);
		}

		/**
		 * Получение значения итератора
		 * @return string значение ячейки
		 */
		function current() {
			return $this->GetValue();
		}

		/**
		 * Получение ключа итератора -- тип ячейки
		 * @return int тип ячейки
		 */
		function key() {
			return $this->GetType();
		}

		/**
		 * Рабочий ли итератор
		 * @return bool рабочий ли итератор
		 */
		function valid() {
			return $this->internalGenerator->valid();
		}

		/**
		 * Конструктор
		 * @param SdmxTableGenerator $parentGenerator родительский генератор таблицы
		 * @param SdmxTableGeneratorSlicesStack $slicesStack стек срезов множества точек
		 */
		function __construct(SdmxTableGenerator $parentGenerator) {
			$this->parentGenerator = $parentGenerator;
			$this->InitInternalIterator(self::FIRST_PART_ITERATOR);
		}
	}
?>
