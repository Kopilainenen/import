<?php

require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");

use \CUser;
use Bitrix\Main\Config\Option;

use Bitrix\Main;
use Bitrix\Highloadblock\HighloadBlockTable as HL;
use Bitrix\Main\Web\Uri;
use Bitrix\Main\Localization\Loc;
use Bitrix\Catalog\Product\Basket;
use Bitrix\Main\Loader;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Sale;
use Bitrix\Main\Page\Asset;



class UserImport {
    private $ftp_server = "80.93.48.179";
    private $ftp_username = "user1C";
    private $ftp_password = "tavrUser1C";
    private $ftp_file = "/user/user_8403.json";

    private $arCurrentUserFields = [];
    private $PERSON_TYPE_ID = 2;
    private $INN = 27;

    public function __construct() {
        if (
            !Loader::includeModule('catalog') &&
            !Loader::includeModule('sale')
        ) {
            ShowError(Loc::getMessage('MODULE_NOT_INSTALLED'));
            return;
        }

    }

    // Для скачивания файла с FTP
    public function downloadFileFromFtp() {
        $conn_id = ftp_connect($this->ftp_server);
        $login_result = ftp_login($conn_id, $this->ftp_username, $this->ftp_password);
        ftp_pasv($conn_id, true);

        if ($login_result) {
            $local_file = $_SERVER['DOCUMENT_ROOT'] . '/local/import/file.json';
            $server_file = $this->ftp_file;
            $res=ftp_get($conn_id, $local_file, $server_file, FTP_BINARY);

            if ($res) {
                echo "Файл успешно загружен\n";
            } else {
                echo "При загрузке файла возникла проблема\n";
            }
        }

        ftp_close($conn_id);
    }

    //Для парсинга JSON файла и создания пользователей
    public function parseJsonAndCreateUsers() {

        $json_string = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/local/import/file.json');


        $json_data = json_decode($json_string, true);


        foreach ($json_data['user'] as $user_data) {
            $user_fields = array(
                'NAME' => $user_data['Имя'],
                'LAST_NAME' => $user_data['Фамилия'],
                'LOGIN' => $user_data['Email'],
                'PASSWORD'=>'000000',
                'EMAIL' => $user_data['Email'],
                'PHONE' => $user_data['Телефон'],
                'UF_CODE_1C' => $user_data['UF_CODE_1C'],
                'UF_IS_BONUS' => $user_data['UF_IS_BONUS'],
                'UF_IS_BOUNS_AUTH' => $user_data['UF_IS_BOUNS_AUTH']
            );
            $data_return['COMPANY_NAME']=$user_data['Наименование компании'];
            $data_return['COMPANY_INN']=$user_data['ИНН'];

            //проверяем, существует ли пользователь с таким же значением поля UF_CODE_1C
            $userFilter = array("UF_CODE_1C" => $user_data["UF_CODE_1C"]);
            $userExists = \CUser::GetList($by = "ID", $order = "ASC", $userFilter)->Fetch();



            if (!$userExists) {
                $user = new CUser;
                $user_id = $user->Add($user_fields);
                if (intval($user_id) > 0) {
                    echo "Пользователь успешно добавлен\n";
                    $data_return['USER_ID']=$user_id;
                } else {
                    echo "При добавлении пользователя возникла проблема\n";
                    echo $user->LAST_ERROR;
                    $data_return['USER_ID']=false;
                }
            }
            else
            {
                echo "Пользователь с UF_CODE_1C = " . $user_data["UF_CODE_1C"] . " уже существует\n";
                $data_return['USER_ID']=$userExists['ID'];
            }

        }
        return $data_return;
    }

    public function addCompanyAction($user_id,$data_in){


        $by = 'sort';$order = 'asc';
        $arFilter = [
            "PERSON_TYPE_ID" => 2,
            "ACTIVE" => 'Y',
            "USER_PROPS" => 'Y'
        ];
        $dbResultList = CSaleOrderProps::GetList(
            array($by => $order),
            $arFilter,
            false,
            false
        );

        $arTypeClear = [
            'YUR_ADRESS' => 'adress',
            'POST_ADRESS' => 'adress',
            'NAME_COMPANY' => 'suggest',
            'NAME_BANK' => 'bank',
            'KPP' => 'suggest_kpp',
            'INN' => 'suggest_inn',
            'BIK_BANK' => 'bank_bik'
        ];
        $arMask = [
            'KPP' => [
                'MASK' => "v-mask=\"'#########'\"",
                'MIN' => '9',
                'ERROR' => 'Значение обязательно для заполнения длина 9 цифр'
            ],
            'INN' => [
                'MASK' => "v-mask=\"'############'\"",
                'MIN' => '10',
                'ERROR' => 'Значение обязательно для заполнения минимальное длина 10 цифр'
            ],
            'BIK_BANK' => [
                'MASK' =>  "v-mask=\"'#########'\"",
                'MIN' => "9",
                'ERROR' => 'Значение обязательно для заполнения длина 9 цифр'
            ],
            'CHECKING' => [
                'MASK' => "v-mask=\"'####################'\"",
                'MIN' => '20',
                'ERROR' => 'Значение обязательно для заполнения длина 20 цифр'
            ],
            'COR_CHET' => [
                'MASK' =>  "v-mask=\"'####################'\"",
                'MIN' => "20",
                'ERROR' => 'Значение обязательно для заполнения длина 20 цифр'
            ],
        ];
        $f_company=[];
        while ($arP = $dbResultList->Fetch())
        {
            $arP['VALUE'] = '';
            if($arTypeClear[$arP['CODE']])
            {
                $arP['DADATA_TYPE'] = $arTypeClear[$arP['CODE']];
            }
            if($arMask[$arP['CODE']])
            {
                $arP['DOP_SET'] = $arMask[$arP['CODE']];
            }
            $f_company[] = $arP;
        }
        $data=[

            "ACTION_TABLE" => "add",
            "NAME_COMPANY" => $data_in['COMPANY_NAME'],
            "INN" => $data_in['COMPANY_INN'],
        ];

        if($f_company)
        {
            foreach ($f_company as $f)
            {
                if($f['IS_PROFILE_NAME'] == 'Y' && $data[$f['CODE']])
                {
                    $pName[] = $data[$f['CODE']];
                }
                if($data[$f['CODE']])
                {
                    $arFData[$f['ID']] = $data[$f['CODE']];
                }
            }
            if(!$pName)
            {
                $pName[] = $data['NAME_COMPANY'];
            }
        }
        //$u_id=40446;//$USER->GetID()
        $arFieldsU = array(
            "NAME" => implode(', ',$pName),
            "USER_ID" => $user_id,
            "PERSON_TYPE_ID" => 2
        );

        if(empty($arFieldsU['NAME']))
        {
            return  [
                'error' => 'Наименьвание не может быть пустым '
            ];
        }

        if($arFData['ID'])
        {

            $USER_PROPS_ID = $arFData['ID'];
            unset($arFData['ID']);
            CSaleOrderUserProps::Update($USER_PROPS_ID,$arFieldsU);
            CSaleOrderUserProps::DoSaveUserProfile($user_id, $USER_PROPS_ID, '', 2, $arFData, $arErrors);
        }else{

            try{
                $USER_PROPS_ID = CSaleOrderUserProps::Add($arFieldsU);
                CSaleOrderUserProps::DoSaveUserProfile($user_id, $USER_PROPS_ID, '', 2, $arFData, $arErrors);
            }catch (Exception $e)
            {
                return  [
                    'error' => $e->getMessage()
                ];
            }

        }





    }


}

$user_import = new UserImport();
$user_import->downloadFileFromFtp();
$data=$user_import->parseJsonAndCreateUsers();
//$user_import->addCompanyAction($SITE_ID,$arFeilds);
if($data['USER_ID'])
    $user_import->addCompanyAction($data['USER_ID'],$data);





