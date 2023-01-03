<?php

namespace App;

class PayStarErrorsHandler
{
    private $ipgErrorDict = [
        ['-1', 'invalidRequest', "درخواست نامعتبر (خطا در پارامترهای ورودی)"],
        ['-2', 'inactiveGateway', "درگاه فعال نیست"],
        ['-3', 'retryToken', "توکن تکراری است"],
        ['-4', 'amountLimitExceed',    "مبلغ بیشتر از سقف مجاز درگاه است"],
        ['-5', 'invalidRefNum',    "شناسه ref_num معتبر نیست"],
        ['-6', 'retryVerification',    "تراکنش قبلا وریفای شده است"],
        ['-7', 'badData',    "پارامترهای ارسال شده نامعتبر است"],
        ['-8', 'trNotVerifiable',    "تراکنش را نمیتوان وریفای کرد"],
        ['-9', 'trNotVerified',    "تراکنش وریفای نشد"],
        ['-98', 'paymentFailed',    "تراکنش ناموفق"],
        ['-99', 'error',    "خطای سامانه"],
        ['-1000', 'The payment finished successfully, But the card-number that used in payment action not the same card-number that your registered in checkout page, Your money will be back to your account in next 72 Hours.',    "پزداخت موفق آمیز بود اما چون شماره کاری که در پرداخت استفاده شده همان شماره کارتی نیست که در صفحه ضورتحساب وارد کرده اید, مبلغ به حساب شما در طی ۷۲ ساعت عودت خواهد شد"],
    ];

    public function getFullMessageByErrorCode($error_code)
    {
        return self::getEnglishMessageByErrorCode($error_code) . " " . self::getPersianMessageByErrorCode($error_code);
    }

    public function getEnglishMessageByErrorCode($error_code)
    {
        $_error_codes = array_column($this->ipgErrorDict, 0);
        return $this->ipgErrorDict[array_search($error_code, $_error_codes)][1];
    }

    public function getPersianMessageByErrorCode($error_code)
    {
        $_error_codes = array_column($this->ipgErrorDict, 0);
        return $this->ipgErrorDict[array_search($error_code, $_error_codes)][2];
    }
}
