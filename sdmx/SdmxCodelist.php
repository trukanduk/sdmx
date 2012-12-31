<?php
	/**
	 * Описание <var>Codelist</var>'ов и сопутствующего
	 *
	 * @author Илья Уваренков <trukanduk@gmail.com>
	 * @version 1.2
	 * @package sdmx
	 */

	require_once('XmlParseUtils.php');

	/**
	 * Элемент <var>Codelist</var>'а
	 *
	 * Один элемент из <var>Codelist</var>'а, содержит в себе имя (идентификатор) и описание на человеческом языке
	 * По сути соответствует коду:
	 * <code>
	 * &lt;structure:Code value="NAME">
	 *     &lt;structure:Description>DESCRIPTION&lt;/structure:Description>
	 * &lt;/structure:Code>
	 * </code>
	 * Стоит отметить, что объекты являются константными, то есть после создания их нельзя изменить, а значит, нет смысла в копировании при передаче их куда-либо
	 *
	 * @package sdmx
	 * @version 1.2
	 */
	class SdmxCodelistInstance {
		/**
		 * Имя элемента
		 *
		 * Нельзя изменить вне класса.
		 *
		 * @var string 
		 */
		protected $name = '';

		/**
		 * Получение имени
		 * 
		 * @return string имя константы
		 */
		function GetName() { return $this->name; }

		/**
		 * Установка имени
		 * 
		 * @param string $name новое имя
		 * @return SdmxCodelistInstance возвращает сам объект
		 */
		protected function SetName($name) {
			$this->name = $name;
			return $this;
		}


		/**
		 * Описание элемента
		 *
		 * Нельзя изменить вне класса
		 * 
		 * @var string
		 */
		protected $description = '';

		/**
		 * Получение описания
		 *
		 * @return string описание элемента
		 */
		function GetDescription() { return $this->description; }

		/**
		 * Установка описания
		 *
		 * @param string $description новое описание
		 * @return SdmxCodelistInstance сам объект
		 */
		protected function SetDescription($description) {
			$this->description = $description;
			return $this;
		}

		/**
		 * Конструктор
		 *
		 * Создаёт новый элемент. Нельзя изменять поля после создания, а потому создавать надо сразу со всеми параметрами
		 *
		 * @param string $name имя элемента
		 * @param string $description описание элемента
		 */
		function __construct($name, $description) {
			$this->SetName($name)
			     ->SetDescription($description);
		}

		/**
		 * Создание элемента из <var>XmlDataArray</var>
		 *
		 * "Достаёт" элемент из объекта <var>XmlDataArray</var> и перемещает его внутренний указатель.
		 *
		 * @param XmlDataArray $arr объект с обработанным xml-файлом и курсором перед тегом (или на самом теге) с началом объекта 
		 * @return SdmxCodelistInstance новый экземпляк класса
		 */
		static function Parse(XmlDataArray $arr) {
			// Найдём начало элемента и сразу достанем имя элемента
			$name = $arr->SkipTo('STRUCTURE:CODE', 'open')->GetAttrValue('VALUE');

			$description = $arr->SkipTo('STRUCTURE:DESCRIPTION', 'complete')->GetValue('value');

			// пропустим всё до конца и поставим указатель на тег после окончания элемента
			$arr->Skip('STRUCTURE:CODE', 'close');
			return new SdmxCodelistInstance($name, $description);
		}
	}

	/**
	 * <var>Codelist</var>
	 *
	 * Некий список неких элементов :) В Xml такой пример:
	 * <code>
	 * &lt;structure:Codelist id="NAME">
	 *	  &lt;structure:Name>VALUE&lt;/structure:Name>
	 *	  &lt;structure:Code value="CONSTANT_NAME_0">
	 *	  	  &lt;structure:Description>CONSTANT_DESCRIPTION_0&lt;/structure:Description>
	 *	  &lt;/structure:Code>
	 *	  &lt;structure:Code value="CONSTANT_NAME_1">
	 *	   	  &lt;structure:Description>CONSTANT_DESCRIPTION_1&lt;/structure:Description>
	 *	  &lt;/structure:Code>
	 * &lt;/structure:Codelist>
	 * </code>
	 * Объект содержит в себе массив объектов <var>SdmxCodelistInstance</var>
	 *
	 * Объект неизменяем вне самого себя, поэтому можно безболезненно передавать его куда-либо
	 *
	 * @package sdmx
	 * @version 1.1
	 */
	class SdmxCodelist {
		/**
		 * Имя
		 *
		 * Имя списка, в xml называется идентификатором (<var>id</var>). Свойство read-only.
		 *
		 * @var string
		 */
		protected $name = '';

		/**
		 * Получение имени
		 *
		 * @return string имя списка (в xml -- <var>id</var>)
		 */
		function GetName() { return $this->name; }

		/**
		 * Установка имени
		 *
		 * @param string $name новое имя
		 * @return SdmxCodelist сам объект
		 */
		protected function SetName($name) {
			$this->name = $name;
			return $this;
		}


		/**
		 * Описание списка
		 *
		 * Имя списка на человеческом языке. в файле имеет название "<var>name</var>". Свойство read-only
		 *
		 * @var string
		 */
		protected $description = '';

		/**
		 * Получение описания
		 *
		 * @return string описание объекта
		 */
		function GetDescription() { return $this->description; }

		/**
		 * Установка описания
		 *
		 * @param string $description новое описание
		 * @return SdmxCodelist сам объект
		 */
		protected function SetDescription($description) {
			$this->description = $description;
			return $this;
		}


		/**
		 * Массив элементов
		 *
		 * Массив элементов списка -- экземпляров <var>SdmxCoelistInstance</var>
		 * Хранятся в виде <code>array('&lt;имя элемента>' => SdmxCodelistInstance('&lt;имя элемента>', '&lt;описание элемента>'), ...)</code>
		 * Имя элемента не зависит от регистра.
		 * Каждый из них неизменяем после создания, поэтому можно не копировать их при передаче
		 *
		 * @var SdmxCodelistInstance[]
		 */
		protected $instances = array();

		/**
		 * Получение элемента списка
		 *
		 * Возвращает элемент списка с именем <var>$name</var> или <var>$defult</var> если такого элемента нет
		 * имя элемента не зависит от регистра
		 *
		 * @param string $name имя элемента (не зависит от регистра)
		 * @param mixed $default возвращается, когда не находится элемента с именем <var>$name</var>. По умолчанию установлен в false
		 * @return mixed Элемент списка (<var>SdmxCodelistInstance</var>) или <var>$default</var> если не найдено
		 */
		function GetInstance($name, $default = false) {
			if (isset($this->instances[strtolower($name)]))
				return $this->instances[strtolower($name)];
			else
				return $default;
		}

		/**
		 * Добавление элемента в список
		 *
		 * Добавляет в список уже созданный элемент. Также, если массив элементов был пустым, выставляет его в качестве элемента по умолчанию.
		 * Имя элемента не зависит от регистра (т.е. <var>'Key'</var> и <var>'key'</var> -- одинаковые элементы)
		 *
		 * @param SdmxCodelistInstance $inst элемент, который будет добавлен в список
		 * @return SdmxCodelist сам объект Codelist
		 */
		protected function AddInstance(SdmxCodelistInstance $inst) {
			$this->instances[strtolower($inst->GetName())] = $inst;
			// Если отсутствует элемент по умолчанию, сделаем $inst таковым
			if ($this->defaultInstanceName === '')
				$this->defaultInstanceName = $inst->GetName();
			return $this;
		}

		/**
		 * Установка всего списка
		 *
		 * Полностью копирует данный массив в себя
		 * Массив должен иметь одну из форм (можно несколько в одной): <var>['&lt;name>' => new SdmxCodelistInstance(), '&lt;name>' => '&lt;desc>', new SdmxCodelistInstance()]</var>
		 *
		 * @param SdmxCodelistInstance[] $arr массив с элементами
		 * @return SdmxCodelist сам объект
		 */
		protected function SetInstanceArray($arr) {
			// Обнулим массив
			$this->instances = array();

			foreach ($arr as $key => $val) {
				// Учитываем все возможные формы массива:
				if (is_a($val, SdmxCodelistInstance)) {
					// если значение -- объект, просто забиваем на его ключ в массиве
					$this->instances[$val->GetName()] = $val;
				} else {
					// осталась форма ['&lt;name>' => '&lt;desc>'] -- создадим объект
					$this->instances[$key] = new SdmxCodelistInstance($key, $inst);
				}
			}

			// Теперь проверим, а есть ли у нас вообще элемент по умолчанию в новом массиве и удалим его имя, если (теперь?) его нет
			if ( ! isset($this->instances[$this->defaultInstanceName]))
				$this->SetDefaultInstanceName('');

			return $this;
		}

		/**
		 * Получение итератора списка
		 *
		 * Возвращает итератор на начало массива элементов
		 *
		 * @return ArrayIterator итератор на начало массива элементов
		 */
		function GetInstancesIterator() {
			return new ArrayIterator($this->instances);
		}

		/**
		 * Элемент по умолчанию
		 *
		 * В некоторых случаях оказывается, что должен быть элемент списка по умолчанию.
		 * В этом поле содержится имя этого элемента в массиве элементов
		 * Он может быть установлен из функции <var>self::SetDefaultInstanceName()</var> или если он будет будет первым в массиве (см. функцию <var>self::AddCInstance()</var>)
		 *
		 * @var string
		 */
		protected $defaultInstanceName = '';

		/**
		 * Получение имени элемента по умолчанию
		 *
		 * @return string имя элемента по умолчанию
		 */
		function GetDefaultInstanceName() { return $this->defaultInstanceName; }

		/**
		 * Получение объекта по умолчанию
		 *
		 * Если элемента с таким именем нет, то вернётся <var>$default</var>
		 *
		 * @param mixed $default значение, возвращаемое при отсутствии элемента по уполчанию
		 * @return mixed элемент списка по умолчанию (<var>SdmxCodelistInstance</var>) или <var>$default</var> в случае отсутствия
		 */
		function GetDefaultInstance($default = false) { return $this->GetInstance($this->defaultInstanceName, $default); }

		/**
		 * Установка имени элемента по умолчанию
		 *
		 * Функция не проверяет существование этого элемента
		 *
		 * @param string $defaultInstanceName имя нового элемента по умолчанию
		 * @return SdmxCodelist сам объект, из которого вызывался метод
		 */
		protected function SetDefaultInstanceName($defaultInstanceName) {
			$this->defaultInstanceName = $defaultInstanceName;
			return $this;
		}

		/**
		 * Конструктор
		 *
		 * Создаёт новый объект и сразу присваивает ему имя и описание. Есть возможность передать массив и имя элемента по умолчанию
		 * Если имя элемента по умолчанию - <var>null</var>, то будет взят первый элемент списка. Если и список пуст, не будет установлен вовсе.
		 *
		 * @param string $name имя списка
		 * @param string $description описание списка
		 * @param SdmxCodelistInstance[] $instances массив элементов списка в юбой из форм: <var>['&lt;name>' => new SdmxCodelistInstance(), '&lt;name>' => '&lt;desc>', new SdmxCodelistInstance()]</var> По умолчанию - пустой.
		 * @param string $defaultInstanceName имя элемента по умолчанию. По умолчанию - <var>null</var>, тогда будет взят первый элемент массива (если не пуст)
		 */
		function __construct($name, $description, $instances = array(), $defaultInstanceName = null) {
			// Установим имя, описание и массив элементов (даже если пустой, хуже не будет)
			$this->SetName($name)
			     ->SetDescription($description)
			     ->SetInstanceArray($instances);

			// выставим значение по умолчанию, если оно было передано или сделаем таковым первый элемент списка (если массив не пустой)
			if ( ! is_null($defaultInstanceName))
				$this->SetDefaultInstanceName($defaultInstanceName);
			elseif (count($instances) > 0) {
				reset($instances);
				$this->SetDefaultInstanceName(key($instances));
			}
		}

		/**
		 * Создание элемента из XmlDataArray
		 *
		 * "Достаёт" элемент из объекта <var>XmlDataArray</var> и перемещает его внутренний указатель.
		 *
		 * @param XmlDataArray $arr объект с обработанным xml-файлом и курсором перед тегом (или на самом теге) с началом объекта 
		 * @return SdmxCodelist новый экземпляр класса
		 */
		static function Parse(XmlDataArray $arr) {
			// Найдём начало списка и сразу выделим его имя
			$name = $arr->SkipTo('STRUCTURE:CODELIST', 'open')->GetAttrValue('ID');
			// Выделим его описание
			$description = $arr->SkipTo('STRUCTURE:NAME')->GetValue('value');

			$ret = new SdmxCodelist($name, $description);

			// Считаем, что он не может быть пустым (какой в нём тогда смысл?)
			$arr->SkipTo('STRUCTURE:CODE');
			// Пока мы стоим на начале нового элемента
			while ($arr->Curr('tag') == 'STRUCTURE:CODE') {
				// распарсим этот элемент и добавим его в массив
				$ret->AddInstance(SdmxCodelistInstance::Parse($arr));
				// на всякий случай пропустим cdata-теги
				$arr->SkipCdata();
			}

			// установим указатель $arr на элемент после окончания списка
			$arr->Skip('STRUCTURE:CODELIST', 'close');
			return $ret;
		}
	}

	// некоторые codelists, которые предполагаются имеющимися (напр., OKSM)
	/**
	 * Объект со стандартными <var>Codelist</var>'ами
	 *
	 * Абстрактный класс со статическим массивом стандартных <var>Codelist</var>'ов. Сейчас есть только <var>OKSM</var> да и то с одним значением
	 *
	 * @version 0.1
	 * @package sdmx
	 */
	abstract class SdmxPredefinedCodelists {
		/**
		 * Массив списков
		 *
		 * Массив стандартных списков, заполняется по мере обращения к нему
		 * @var SdmxCodelist[]
		 */
		static protected $codelists = array();

		/**
		 * Получение конкретного списка
		 *
		 * Возвращает список с заданным именем <var>$name</var> (в том числе пытается его создать в случае отутствия), если имя списка неизвестно, вернёт <var>$default</var>
		 * Имя списка не зависит от регистра
		 *
		 * @param string $name имя списка (в xml-файле - <var>id</var>), не зависит от регистра
		 * @param mixed $default значение по умолчанию, возвращаемое в случае отсутствия искомого списка
		 * @return mixed искомый список с именем <var>$name</var> (<var>SdmxCodelist</var>) или <var>$default</var> в случае отсутствия такового.
		 */
		static function GetCodelist($name, $default = false) {
			if ( ! isset(self::$codelists[strtolower($name)])) {
				// по возможности создадим этот codelist
				if (strtolower($name) == 'oksm')
					self::$codelists['oksm'] = new SdmxCodelist('OKSM',
			                                                    'Общероссийский классификатор стран мира',
		                                                        array('rus' => 'Российская федерация'),
		                                                        'rus');
			}
			// Если его удалось создать или он уже был закеширован, вернём его, иначе вернём $default
			if (isset(self::$codelists[strtolower($name)]))
				return self::$codelists[strtolower($name)];
			else
				return $default;
		}
	}
?>
