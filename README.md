**Architecture**:
- Couchbase Lite 
- Sync Gateway
- Couchbase DB Server

Required Frameworks:
http://docs.slimframework.com/
http://docs.couchbase.com/couchbase-sdk-php-1.1/

Authentication is negociated using the server side API.
https://username:password@cloud.openmoney.cc/login
which sets a session cookie for the domain.

P2P Archetecture:
Couchbase Lite > couchbase lite server (httpd) > Couchbase Lite

P2P Routing:

Peer to Peer routing is done using NFC peer to peer

