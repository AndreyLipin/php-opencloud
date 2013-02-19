<?php
// (c)2012 Rackspace Hosting
// See COPYING for licensing information

require_once('rackspace.php');

define('AUTHURL', 'https://identity.api.rackspacecloud.com/v2.0/');
define('USERNAME', $_ENV['OS_USERNAME']);
define('TENANT', $_ENV['OS_TENANT_NAME']);
define('APIKEY', $_ENV['NOVA_API_KEY']);
define('REGION', $_ENV['OS_REGION_NAME']);

// establish our credentials
$cloud = new OpenCloud\Rackspace(AUTHURL,
	array( 'username' => USERNAME,
		   'apiKey' => APIKEY ));

// DNS service
$dns = $cloud->DNS();

// uncomment for debug output
//setDebug(TRUE);

// compute service
$compute = $cloud->Compute(NULL, REGION);
$slist = $compute->ServerList();
while($server = $slist->Next()) {
	printf("PTR records for Server [%s]:\n", $server->Name());
	try {
		$ptrlist = $dns->PtrRecordList($server);
		while($ptr = $ptrlist->Next()) {
			printf("- %s=%s\n", $ptr->data, $ptr->name);
			printf("- comment: %s\n", $ptr->comment);
			printf("  modifying...\n");
			$ptr->comment = sprintf('Updated at %s', date('H:i:s'));
			$aresp = $ptr->Update($server);
			$aresp->WaitFor('COMPLETED', 300, 'pstat', 1);
		}
	} catch (\OpenCloud\CollectionError $e) {
		echo "- No records found\n";
	}
}

exit;

function pstat($obj) {
	printf("  ...%s\n", $obj->Status());
}