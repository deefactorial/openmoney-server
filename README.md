**Architecture**:
- Couchbase Lite 
- Sync Gateway
- Couchbase DB Server

Required Frameworks:
http://docs.slimframework.com/
http://docs.couchbase.com/couchbase-sdk-php-1.1/

Authentication is negociated using the server side API.
https://username:password@cloud.openmoney.cc/login
which returns a sessionID.

Archetecture:

openmoney-mobile -> 
uses couchbase-lite-phonegap -> 
couchbase-lite-java-core REST API -> 
local device database -> 
Sync replication with cloud -> 
Cloud Sync Gateway run by sync-gateway.json rules ->
linearly extensible cloud server database instances -> 
Cross Datacenter Replication (XDCR) 

Propsed P2P Archetecuture:
Local device database -> 
Replicates with local devices P2P using TCP connections
through local wifi connections. Content and routing 
information is exchanged through NFC connections made 
from device to device. remote db connection can be made 
through the internet if an untrusted network can negoticate 
public private RSA key authentication and authroization.



