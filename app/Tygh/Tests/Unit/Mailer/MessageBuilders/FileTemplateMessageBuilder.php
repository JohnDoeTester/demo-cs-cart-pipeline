<?php


namespace Tygh\Tests\Unit\Mailer\MessageBuilders;

class FileTemplateMessageBuilder extends \Tygh\Mailer\MessageBuilders\FileTemplateMessageBuilder
{
    protected function getCompany($company_id, $lang_code, $area = '')
    {
        return MessageBuilder::getCompanyTest($company_id, $lang_code);
    }

    protected function getDefaultCompanyId()
    {
        return MessageBuilder::getDefaultCompanyIdTest();
    }

    protected function getImageSize($real_path)
    {
        return MessageBuilder::getImageSizeTest($real_path);
    }

    protected function getImageExtension($mime_type)
    {
        return MessageBuilder::getImageExtensionTest($mime_type);
    }

    protected function allowedFor($edition)
    {
        return MessageBuilder::allowedForTest($edition);
    }

    public function validateAddress($email)
    {
        return MessageBuilder::validateAddressTest($email);
    }

    public function getStorefrontId()
    {
        return 1;
    }
}