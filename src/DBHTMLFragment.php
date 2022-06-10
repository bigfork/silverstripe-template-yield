<?php

namespace Bigfork\SilverstripeTemplateYield;

use SilverStripe\ORM\FieldType\DBHTMLText;

class DBHTMLFragment extends DBHTMLText
{
    public function forTemplate(): string
    {
        $result = parent::forTemplate();
        return BlockProvider::yieldIntoString($result);
    }
}
