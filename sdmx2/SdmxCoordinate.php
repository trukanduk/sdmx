<?php
	/**
	 * Файл содержит описание координаты по одной оси
	 *
	 * @author Илья Уваренков <trukanduk@gmail.com>
	 * @version 1.0
	 * @package sdmx
	 */

	require_once('SdmxAxis.php');

	/**
	 * Координата на оси
	 * 
	 * Класс описывает координату какой-то ячейки по одной из осей
	 *
	 * @package sdmx
	 * @version 1.0
	 */
	class SdmxCoordinate {
		/**
		 * Значение координаты
		 *
		 * Просто значение. На человеческом языке, чтобы можно было вывести
		 * @var string
		 */
		protected $value = '';

		/**
		 * Получение значения координаты
		 *
		 * @return string значение координаты
		 */
		function GetValue() {
			return $this->value;
		}

		/**
		 * Приведение к строке
		 *
		 * Эквивалентно получнию значения.
		 * @return string значение координаты
		 */
		function __toString() {
			return $this->value;
		}

		/**
		 * Установка координаты
		 *
		 * @param string новое значение
		 * @return SdmxCoordinate объект-хозяин методаы
		 */
		function SetValue($value) {
			$this->value = $value;
			return $this;
		}

		/**
		 * Обновление значения
		 *
		 * Получает новое значение по оси и сырому значению
		 *
		 * @param string $default Значение по умолчанию в случае ошибки
		 * @return SdmxCoordinate объект-хозяин метода
		 */
		function UpdateValue($default = '') {
			if (is_a($this->GetAxis(), SdmxAxis))
				$this->SetValue($this->GetAxis()->GetValue($rawValue), $defaultValue);
			else
				$this->SetValue($defaultValue);
			return $this;
		}
		/**
		 * "Сырое" значение -- внутри документа
		 *
		 * Сырое значение, используемое внутри документа (id значения в Codelist)
		 * @var string
		 */
		protected $rawValue = '';

		/**
		 * Получение сырого значения
		 *
		 * @return string сырое значение
		 */
		function GetRawValue() {
			return $this->rawValue;
		}

		/**
		 * Установка сырого значения
		 *
		 * @param string $rawValue новое сырое значение
		 * @param bool $updateValue Если этот флаг выставлен в true, то значение координаты тоже обновится в соответствии с размерностью
		 * @param string $defaultValue Если флаг $updateValue выставлен в true, то в этом параметре хранится
		 *                             значение, которое будет выставлено в случае, если  не будет найдено значения в оси
		 * @return SdmxCoordinate объект-хозяин метода
		 */
		function SetRawValue($rawValue, $updateValue = false, $defaultValue = '') {
			$this->rawValue = $rawValue;
			if ($updateValue)
				$this->UpdateValue($defaultValue);
			return $this;
		}

		/**
		 * Ось, которой принадлежит координата
		 *
		 * @param SdmxAxis
		 */
		protected $axis;

		/**
		 * Получение оси
		 *
		 * @return SdmxAxis ось координаты
		 */
		function GetAxis() {
			return $this->axis;
		}

		/**
		 * Получение идентификатора оси
		 *
		 * @param mixed $default значение по умолчанию (если ось не установлена)
		 * @return string идентификатор оси (строка) или <var>$default</var>
		 */
		function GetAxisId($default = false) {
			if (is_a($this->GetAxis(), SdmxAxis))
				return $this->GetAxis()->GetId();
			else
				return $default;
		}

		/**
		 * Установка оси
		 *
		 * @param SdmxAxis $axis новая ось
		 * @param bool $updateValue флаг обновления значения
		 * @param string $defaultValue новое значение координаты в случае неуспешного обновления 
		 * @return SdmxCoordinate объект-хозяин метода
		 */
		function SetAxis(SdmxAxis $axis, $updateValue = false, $defaultValue = '') {
			$this->axis = $axis;
			if ($updateValue)
				$this->UpdateValue($defaultValue);
			return $this;
		}

		/**
		 * Операция сравнения двух координат
		 *
		 * Сравнивает две координаты на одной оси (!), возвращает отрицательно значение,
		 * если первое меньше, положительное число, если второе больше и ноль при равенстве.
		 *
		 * @throws Exception если координаты из разных осей
		 * @param SdmxCoordinate $fisrt Первая координата
		 * @param SdmxCoordinate $second Вторая координата
		 * @return int отрицательное, если первое меньше, положительное, если второе меньше, ноль при равенстве
		 */
		static function Compare(SdmxCoordinate $first, SdmxCoordinate $first) {
			if ($first->GetAxis() !== $second->GetAxis())
				throw new Exception('Coordinates in different axes!');

			return $first->GetAxis()->Compare($first->GetRawValue(), $first->GetRawValue());
		}

		/**
		 * Конструктор
		 *
		 * Получает на вход ось и сырое значение, устанавливает оба и пытается обновить значение,
		 * В случае неудачного обновления выставляет значение по умолчанию (<var>$defaultValue</var>)
		 *
		 * @param SdmxAxis $axis ось координаты
		 * @param string $rawValue сырое значение
		 * @param string $defaultValue значение по умолчанию, если при обновлении возникнут траблы
		 */
		function __construct(SdmxAxis $axis, $rawValue, $defaultValue = '') {
			$this->SetAxis($axis, false)
			     ->SetRawValue($rawValue, false)
			     ->UpdateValue($defaultValue);
		}

		function __DebugPrint() {
			echo "({$this->GetAxisId('')}: {$this->GetRawValue()}, {$this->GetValue()}) ";
			return $this;
		}
	}
?>
