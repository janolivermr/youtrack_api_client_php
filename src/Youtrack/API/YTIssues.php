<?php namespace Youtrack\API;


class YTIssues extends YTClientAbstract{

    public function __construct(YTClient $client){
        parent::__construct($client);
    }

    /**
     * @param $issueId
     *
     * @return mixed
     * @throws \Exception
     */
    public function find($issueId){
        $path = '/issue/'.$issueId;
        list(, $result) = $this->client->apiRequest($path, 'GET');
        if($result === '<error>Issue not found.</error>'){
            throw new \Exception('Issue not found');
        }
        return $result;
    }

    /**
     * @param $issueId
     *
     * @return mixed
     * @throws \Exception
     */
    public function history($issueId){
        $path = '/issue/'.$issueId.'/history';
        list(, $result) = $this->client->apiRequest($path, 'GET');
        if($result === '<error>Issue not found.</error>'){
            throw new \Exception('Issue not found');
        }
        return $result;
    }

    /**
     * @param $issueId
     *
     * @return mixed
     * @throws \Exception
     */
    public function changes($issueId){
        $path = '/issue/'.$issueId.'/changes';
        list(, $result) = $this->client->apiRequest($path, 'GET');
        if($result === '<error>Issue not found.</error>'){
            throw new \Exception('Issue not found');
        }
        return $result;
    }

    /**
     * @param $issueId
     *
     * @return bool
     * @throws \Exception
     */
    public function exists($issueId){
        $path = '/issue/'.$issueId.'/exists';
        list($status, ) = $this->client->apiRequest($path, 'GET');
        if($status == 404){
            return false;
        }elseif($status == 200){
            return true;
        }else{
            throw new \Exception('Unexpected status code: '.$status);
        }
    }

    /**
     * @param $project
     * @param array $options
     *
     * @return mixed
     * @throws \Exception
     */
    public function byProject($project, $options = []){
        $path = '/issue/byproject/'.$project.'?';
        if(array_key_exists('filter', $options)){
            $path .= 'filter='.urlencode($options['filter']).'&';
        }
        if(array_key_exists('after', $options) && is_numeric($options['after'])){
            $path .= 'after='.$options['after'].'&';
        }
        if(array_key_exists('max', $options) && is_numeric($options['max'])){
            $path .= 'max='.$options['max'].'&';
        }
        if(array_key_exists('updatedAfter', $options) && is_numeric($options['updatedAfter'])){
            $path .= 'updatedAfter='.$options['updatedAfter'].'&';
        }
        if(array_key_exists('wikifyDescription', $options) && is_bool($options['wikifyDescription'])){
            $path .= 'wikifyDescription='.$options['wikifyDescription'];
        }
        list(, $result) = $this->client->apiRequest($path, 'GET');
        if($result === '<error>Issue not found.</error>'){
            throw new \Exception('Issue not found');
        }
        return $result;
    }
}