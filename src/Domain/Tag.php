<?php

declare(strict_types=1);

namespace iBudasov\Iptc\Domain;

class Tag
{
    public const TYPE = 2;

    public const OBJECT_NAME = '005';
    public const EDIT_STATUS = '007';
    public const PRIORITY = '010';
    public const CATEGORY = '015';
    public const SUPPLEMENTAL_CATEGORY = '020';
    public const FIXTURE_IDENTIFIER = '022';
    public const KEYWORDS = '025';
    public const RELEASE_DATE = '030';
    public const RELEASE_TIME = '035';
    public const SPECIAL_INSTRUCTIONS = '040';
    public const REFERENCE_SERVICE = '045';
    public const REFERENCE_DATE = '047';
    public const REFERENCE_NUMBER = '050';
    public const CREATED_DATE = '055';
    public const CREATED_TIME = '060';
    public const ORIGINATING_PROGRAM = '065';
    public const PROGRAM_VERSION = '070';
    public const OBJECT_CYCLE = '075';
    public const AUTHOR = '080';
    public const CITY = '090';
    public const PROVINCE_STATE = '095';
    public const COUNTRY_CODE = '100';
    public const COUNTRY = '101';
    public const ORIGINAL_TRANSMISSION_REFERENCE = '103';
    public const HEADLINE = '105';
    public const CREDIT = '110';
    public const SOURCE = '115';
    public const COPYRIGHT_STRING = '116';
    public const DESCRIPTION = '120';
    public const LOCAL_CAPTION = '121';
    public const CAPTION_WRITER = '122';

    /**
     * @var string which will be converted to int later
     */
    private $code;

    /**
     * @var string
     */
    private $value;

    /**
     * @param string[] $valuesOfTag
     */
    public function __construct(string $codeOfTag, array $valuesOfTag)
    {
        $this->code = $codeOfTag;
        $this->value = $valuesOfTag;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @return string[]
     */
    public function getValues(): array
    {
        return $this->value;
    }

    public function __toString()
    {
        return \implode(', ', $this->getValues());
    }
}
