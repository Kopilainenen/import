1 Создаем файл 
local/modules/krayt.catalogimport/lib
userimport.php класс UserImport


2 Там сделать создание пользователя из данных json 
user: [{
  'Email': 'mail@yandex.ru',
  'Имя': 'Алексей',
  'Фамилия': 'Голов',
  'Телефон': '+79111234567',
  'UF_CODE_1C': '8403',
  'UF_IS_BONUS': '1',
  'UF_IS_BOUNS_AUTH': '1',
  'Наименование компании': 'ООО "ТК офисная Служба"',
  'Наименование юридического лица': 'ООО "ТК офисная Служба"',
  'ИНН': '223338382'
}]
discounts: [{
  'Код конрагента': '8403',
  'Группа номенклатуры код': '12',
  'Скидка в %': '15',
  'Наценка от входящей цены в %': '0',
  'Код папки номенклатуры 1с': ''
}, {
  'Код конрагента': '8403',
  'Группа номенклатуры код': '28',
  'Скидка в %': '15',
  'Наценка от входящей цены в %': '0',
  'Код папки номенклатуры 1с': ''
}]
special_price: [{
  'Код товара': '2252',
  'Код конрагента': '8403',
  'Специальная цена': '169.78'
},
{
  'Код товара': '2003',
  'Код конрагента': '8403',
  'Специальная цена': '154.15'
},
{
  'Код товара': '2001 ',
  'Код конрагента': '8403',
  'Специальная цена': '154.15'
}]
  'UF_CODE_1C': '8403',
  'UF_IS_BONUS': '1',
  'UF_IS_BOUNS_AUTH': '1',


Это пользовательские поля она есть на сайте   
'UF_CODE_1C': '8403',
  'UF_IS_BONUS': '1',
  'UF_IS_BOUNS_AUTH': '1',


так же  клас будет должен иметь возможность скачать ftp файл на сервер
создать компанию  по этим полям 
 'Наименование компании': 'ООО "ТК офисная Служба"',
  'Наименование юридического лица': 'ООО "ТК офисная Служба"',
  'ИНН': '223338382'
