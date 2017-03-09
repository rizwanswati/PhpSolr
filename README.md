# PhpSolr
This library can be used to integrate apache Solr with existing MySql database.

## Detail Usage:
The basic usage of the library is below. 

# Query Solr 
``php
$solr = new Basicsolr('core_name');
$response = $solr->query(*:*);
print_r($response);
``

# Get A Document

``php
$solr = new Basicsolr('core_name');
$response = $solr->getColumns('document_name');
print_r($response);
``

to get the multiple documents from the solr you can pas the array of documents name as an argument to getColumn method

# Add Document To Solr

``php
$solr = new Basicsolr('core_name');
$response = $solr->AddnNewUserToSolrCore($resultset) 
//give resultset as an argument. make sure in this method of library you have defined your document as key value pair.
``

# Update The Document

``php
$solr = new Basicsolr('core_name');
$response = $solr->updateRecord($solr_id,$doc_name,$value);
print_r($response);
``

# Delete Document

```php
$solr = new Basicsolr('core_name');
$response = $solr->deleteRecord($solr_id);
print_r($respnse);
```

# Incriment/Decrement Integer Value

``php
$solr = new Basicsolr('core_name');
$response = $solr->IncrimentValue($solr_id,$doc_name,$value)
print_r($response);
``

# Further Documentation
Refer to [Apache Solr's documentation](http://lucene.apache.org/solr/documentation.html)
for more details on the API.
