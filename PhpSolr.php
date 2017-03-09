<?php
/*
 * This Library is written(extended) by Rizwan Ullah. the basic version was written by someone else but some crucial functionlities were missing.
 * Tested with MySql and Codeigniter
 * Tested with Solr 6.XX
*/

if (!defined('BASEPATH'))
    exit('No direct script access allowed');
	
class Basicsolr
{
	private $solrUrl = null;
	private $responseFormat = '&wt=json&indent=true';
	private $facet = null;
	private $edismax = null;
	private $orderBy = null;
	private $fq = '';
	public  $core = '';
	private $solrIP = 'http://solrIP:port/solr/'; //htpp://localhost:8983/solr/


	public function __construct($core='')
	{
		$this->solrUrl = $this->solrIP.$core.'/';
	}

	private function getResponse($url)
	{
		try
		{
			$url = $url.$this->fq.'';
			
			$response = $this->curl_get_file_contents($url);
			if(!is_null($response) && $response !== '')
			{
				$json = json_decode($response);
				$docs = $json->response->docs;
				$totalItens = $json->response->numFound;
				
				$result = new Stdclass();

				$result->items = $docs;
				$result->total = intval($totalItens);
				$result->url = $url;

				if(isset($json->facet_counts))
				{
					$facets = $json->facet_counts->facet_fields;
					$result->facet = $this->decodeFacets($facets);
				}

				return $result;
			}
			else
			{
				return array('error' => 'Solr Exception','exception' => $response);
			}
		}
		catch(Exception $e)
		{
			return array('error' => "Solr Exception", 'exception' => $e->getMessage());
		}
	}

	public function setOrderBy($orderBy)
	{
		$this->orderBy = urlencode($orderBy);
	}

	public function decodeFacets($facet)
	{
		$result = array();
		foreach($facet as $key => $facets)
		{
			for($i = 0; $i < count($facets); $i = $i+2)
			{
				$result[$key][] = array('name' => $facets[$i], 'qty' => $facets[$i+1]);
			}
		}

		return $result;
	}

	public function query($query, $limit = 20, $offset = 0)
	{
		$url = $this->solrUrl.'select?q='.urlencode($query);

		if(trim($this->facet) !== '')
		{
			$url .= $this->facet;
		}

		$url .= "";
		
		if(trim($this->edismax) !== '')
		{
			$url .= $this->edismax;
		}

		$url .= $this->responseFormat."&start={$offset}&rows={$limit}";

		if(!is_null($this->orderBy) && $this->orderBy !== '')
		{
			$url .= '&sort='.$this->orderBy;
		}

		return $this->getResponse($url);
	}

	public function addFq($string)
	{
		$this->fq .= "&fq=".urlencode($string);
	}

	public function facet($fields)
	{
		if(is_array($fields))
		{
			$total = count($fields);
			for($i = 0; $i < $total; $i++)
			{
				$this->facet .= "&facet=true&facet.mincount=1&facet.field=".urlencode($fields[$i]);
			}
		}
		else
		{
			$this->facet = "&facet=true&facet.mincount=1&facet.field=".urlencode($fields);
		}
	}

	public function edismax($fields)
	{
		$this->edismax = "&defType=dismax&qf=".urlencode($fields)."&stopwords=true&lowercaseOperators=true";
	}

	public function deltaImport()
	{
		$url = $this->solrUrl.'dataimport?command=delta-import&commit=true&clean=false&wt=json';
		$response = $this->curl_get_file_contents($url);
		return $response;
	}

	public function fullImport()
	{
		$url = $this->solrUrl.'dataimport?command=full-import&commit=true&clean=true&wt=json';
		$response = $this->curl_get_file_contents($url);

		return $response;
	}

	public function importStatus()
	{
		$url = $this->solrUrl.'dataimport?command=status&wt=json';
		$response = $this->curl_get_file_contents($url);

		return json_decode($response);
	}
	public function getResponseFromUrl($url){
		
		return $this->getResponse($url);
		
		
	}
	private function curl_get_file_contents($URL)
    {
        $c = curl_init();
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_URL, $URL);
        $contents = curl_exec($c);
        curl_close($c);

        if ($contents) return $contents;
            else return FALSE;
    }

	public function updateRecord($solr_id,$doc_name,$value)
	{
		$ch = curl_init($this->solrUrl.'update?commit=true');

		$data = array(
			"solr_id" => $solr_id,
			$doc_name => array(
				"set" => $value)
		);

		$data_string = json_encode(array($data));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);

		$response = curl_exec($ch);
		return $response;

	}

	public function deleteRecord($uniqueKey,$value)
	{

		$var="<delete><query>$uniqueKey:$value</query></delete>";
		$url = $this->solrUrl.'update/?commit=true';
        $ch = curl_init();
        $header = array("Content-type:text/xml; charset=utf-8");
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $var);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLINFO_HEADER_OUT, 1);

        $response = curl_exec($ch);
		return $response;

	}

    public function AddnNewUserToSolrCore($newuser)
    {
        $newuser = $newuser[0]; //change according to your data formate.
        $ch = curl_init($this->solrUrl.'update?wt=json');

        $data = array(
            "add" => array(
                "doc" => array(
                    //add your fileds here as key value pair. make sure you have already defined the data-config.xml and solrconfig.xml which reflects your database schema.
					///define fields as below example
					"SolrDocId"   => 1,
					"SolrDocName" => "Value",
                ),
                "commitWithin" => 1000,
            ),
        );

        $data_string = json_encode($data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);

        $response = curl_exec($ch);
		return $response;
    }


	public function IncrimentValue($solr_id,$doc_name,$value)
	{
		$ch = curl_init($this->solrUrl.'update?commit=true');

		$data = array(
			"solr_id" => $solr_id,
			$doc_name => array(
				"inc" => $value)
		);

		$data_string = json_encode(array($data));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);

		$response = curl_exec($ch);
		return $response;

	}

	public function getColumns($colList) //colList is an arraylist of column..(java thing)
	{
		$flag = 1;
		$url = $this->solrUrl.'select?fl=';
		foreach ($colList as $list => $value) {
			if($flag) {
				$url = $url . $value;
				$flag=0;
			}else{
				$url = $url .','. $value;
			}
		}
		$response = $this->getResponse($url);
		return $response;
	}

}

