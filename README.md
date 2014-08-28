Architecture:
Couchbase Lite > Sync Gateway > Couchbase DB Server

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

Documentation is done using httpie found here: https://github.com/jakubroztocil/httpie

Create User example API:


```bash
http POST http://cloud.openmoney.cc/registration username=deefactorial@gmail.com password=password
HTTP/1.1 200 OK
Content-Length: 131
Content-Type: application/json
Date: Mon, 11 Aug 2014 19:12:24 GMT
Server: Apache/2.2.22 (Ubuntu)
Set-Cookie: SyncGatewaySession=1babed8e140cb51f6cecb83679124c90772b4d42
X-Powered-By: PHP/5.3.10-1ubuntu3.13
```
```javascript
{
    "error": false, 
    "expires": "2014-08-12T12:12:25.114909773-07:00", 
    "sessionID": "1babed8e140cb51f6cecb83679124c90772b4d42", 
    "status": 200
}
```

Login example API:

```
http POST http://cloud.openmoney.cc/login username=deefactorial+6@gmail.com password=password
HTTP/1.1 200 OK
Content-Length: 131
Content-Type: application/json
Date: Mon, 11 Aug 2014 19:18:41 GMT
Server: Apache/2.2.22 (Ubuntu)
Set-Cookie: SyncGatewaySession=e033315a7f1561944d38cba6b6ffeec611641073
X-Powered-By: PHP/5.3.10-1ubuntu3.13
```
```javascript
{
    "error": false, 
    "expires": "2014-08-12T12:18:41.425467857-07:00", 
    "sessionID": "e033315a7f1561944d38cba6b6ffeec611641073", 
    "status": 200
}
```

Trading Name Example API:

```
http PUT http://deefactorial+6@gmail.com:password@cloud.openmoney.cc:4984/openmoney_shadow/trading_name,deefactorial6 trading_name=deefactorial6 trading_name_space=cc currency=cc steward=deefactorial+6@gmail.com
HTTP/1.1 201 Created
Content-Length: 88
Content-Type: application/json
Date: Mon, 11 Aug 2014 19:30:42 GMT
Etag: 1-3ae9a28cbf7d3ba7b4df103e1b4d9a53
Server: Couchbase Sync Gateway/1.00

{
    "id": "trading_name,deefactorial6", 
    "ok": true, 
    "rev": "1-3ae9a28cbf7d3ba7b4df103e1b4d9a53"
}
```

```
http --auth jane.victoria.bc.ca:cryptosaltedhash PUT http://localhost:4984/openmoney_shadow/trading_name,jane.victoria.bc.ca trading_name=jane trading_name_space=victoria.bc.ca currency=vi$ steward=jane.victoria.bc.ca
HTTP/1.1 201 Created
Content-Length: 94
Content-Type: application/json
Date: Thu, 05 Jun 2014 19:49:01 GMT
Etag: 1-8781c0fee808e4d22f4f71d950ae3b08
Server: Couchbase Sync Gateway/1.00

{
    "id": "trading_name,jane.victoria.bc.ca", 
    "ok": true, 
    "rev": "1-8781c0fee808e4d22f4f71d950ae3b08"
}

Currency Creation API:

http PUT http://deefactorial+6@gmail.com:password@cloud.openmoney.cc:4984/openmoney_shadow/currency,barter$ type=currency currency=barter$ currency_network=currency_network,cc name='Barter Dollars' steward:='["deefactorial+6@gmail.com"]'
HTTP/1.1 201 Created
Content-Length: 78
Content-Type: application/json
Date: Mon, 11 Aug 2014 19:44:22 GMT
Etag: 1-9071a2752079ac3e6eae48a8dcdb42c4
Server: Couchbase Sync Gateway/1.00

{
    "id": "currency,barter$", 
    "ok": true, 
    "rev": "1-9071a2752079ac3e6eae48a8dcdb42c4"
}


Trading Name Journal Entry Example API:

http PUT http://deefactorial+6@gmail.com:password@cloud.openmoney.cc:4984/openmoney_shadow/trading_name_journal,deefactorial6.cc,deefactorial.cc,2014-08-10T21:39:08.811Z amount=15.00 currency=cc description=message from=deefactorial6.cc to=deefactorial.cc type=trading_name_journal timestamp=2014-08-10T21:39:08.811Z
HTTP/1.1 201 Created
Content-Length: 140
Content-Type: application/json
Date: Tue, 12 Aug 2014 02:07:57 GMT
Etag: 1-7035ca704eeee3940bcf2b2c535b6636
Server: Couchbase Sync Gateway/1.00

{
    "id": "trading_name_journal,deefactorial6.cc,deefactorial.cc,2014-08-10T21:39:08.811Z", 
    "ok": true, 
    "rev": "1-7035ca704eeee3940bcf2b2c535b6636"
}


Trading Name Space Creation Example API:

http --auth jane.victoria.bc.ca:cryptosaltedhash PUT http://localhost:4984/openmoney_shadow/trading_name_space,gabriola.bc.ca steward:='["jane.victoria.bc.ca"]' 
HTTP/1.1 201 Created
Content-Length: 95
Content-Type: application/json
Date: Thu, 05 Jun 2014 20:47:41 GMT
Etag: 1-a0f097ae41598604be91ccd046911399
Server: Couchbase Sync Gateway/1.00

{
    "id": "trading_name_space,gabriola.bc.ca", 
    "ok": true, 
    "rev": "1-a0f097ae41598604be91ccd046911399"
}

Currency Space Creation Example API:

http --auth jane.victoria.bc.ca:cryptosaltedhash PUT http://localhost:4984/openmoney_shadow/currency_space,gabriola.bc.ca steward:='["jane.victoria.bc.ca"]' 
HTTP/1.1 201 Created
Content-Length: 91
Content-Type: application/json
Date: Thu, 05 Jun 2014 20:49:12 GMT
Etag: 1-a0f097ae41598604be91ccd046911399
Server: Couchbase Sync Gateway/1.00

{
    "id": "currency_space,gabriola.bc.ca", 
    "ok": true, 
    "rev": "1-a0f097ae41598604be91ccd046911399"
}


GET a List of all documents available.


http --auth jane.victoria.bc.ca:cryptosaltedhash http://localhost:4984/openmoney_shadow/_all_docs
HTTP/1.1 200 OK
Content-Encoding: gzip
Content-Length: 370
Content-Type: application/json
Date: Thu, 05 Jun 2014 20:50:38 GMT
Server: Couchbase Sync Gateway/1.00

{
    "rows": [
        {
            "id": "currency_space,gabriola.bc.ca", 
            "key": "currency_space,gabriola.bc.ca", 
            "value": {
                "rev": "1-a0f097ae41598604be91ccd046911399"
            }
        }, 
        {
            "id": "currency_space,victoria.bc.ca", 
            "key": "currency_space,victoria.bc.ca", 
            "value": {
                "rev": "1-a1ecc60d004af0fc6ab3b780f5482ad9"
            }
        }, 
        {
            "id": "trading_name_journal,jane.victoria.bc.ca,john.victoria.bc.ca,1401998107", 
            "key": "trading_name_journal,jane.victoria.bc.ca,john.victoria.bc.ca,1401998107", 
            "value": {
                "rev": "1-ad5755391868fb7900a39e872e0b8528"
            }
        }, 
        {
            "id": "trading_name_journal,john.victoria.bc.ca,jane.victoria.bc.ca,1400965833", 
            "key": "trading_name_journal,john.victoria.bc.ca,jane.victoria.bc.ca,1400965833", 
            "value": {
                "rev": "1-3bd3e6b5566eb740376ac3d019907fbe"
            }
        }, 
        {
            "id": "trading_name_space,gabriola.bc.ca", 
            "key": "trading_name_space,gabriola.bc.ca", 
            "value": {
                "rev": "1-a0f097ae41598604be91ccd046911399"
            }
        }, 
        {
            "id": "trading_name,jane.victoria.bc.ca", 
            "key": "trading_name,jane.victoria.bc.ca", 
            "value": {
                "rev": "1-8781c0fee808e4d22f4f71d950ae3b08"
            }
        }, 
        {
            "id": "users,jane.victoria.bc.ca", 
            "key": "users,jane.victoria.bc.ca", 
            "value": {
                "rev": "1-01be0cb20390d8806e8c356d98a2c23e"
            }
        }
    ], 
    "total_rows": 12, 
    "update_seq": 19
}


GET Document ID Example API:

http --auth jane.victoria.bc.ca:cryptosaltedhash http://localhost:4984/openmoney_shadow/trading_name_journal,john.victoria.bc.ca,jane.victoria.bc.ca,1400965833
HTTP/1.1 200 OK
Content-Length: 391
Content-Type: application/json
Date: Thu, 05 Jun 2014 20:53:21 GMT
Etag: 1-3bd3e6b5566eb740376ac3d019907fbe
Server: Couchbase Sync Gateway/1.00

{
    "_id": "trading_name_journal,john.victoria.bc.ca,jane.victoria.bc.ca,1400965833", 
    "_rev": "1-3bd3e6b5566eb740376ac3d019907fbe", 
    "amount": 10, 
    "currency": "vi$", 
    "from": "john.victoria.bc.ca", 
    "from_message": "cryptographically encoded message using john.doe's public key", 
    "timestamp": "1400965833", 
    "to": "jane.victoria.bc.ca", 
    "to_message": "cryptographically encoded message using jane.doe's public key"
}








