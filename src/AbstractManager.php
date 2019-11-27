<?php

namespace AmoClient;

abstract class AbstractManager
{
    use PropertiesBehaviorTrait;

    /** @var HttpClient $httpClient */
    protected $httpClient;

    /**
     * AbstractManager constructor.
     * @param array $config
     * @throws \Exception
     * @throws \Exception
     */
    public function __construct(array $config = [])
    {
        $config = static::prepareProperties($config);

        if(static::hasErrors()) {
            throw new \Exception(static::getStringErrors());
        }

        $this->properties = $config;
        $this->httpClient = new HttpClient();
    }
}