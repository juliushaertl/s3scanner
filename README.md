# S3Scanner

Helper app for Nextcloud in order to avoid massive oc_filecache updates during migration tasks and defer them to a later API request once all user data has been migrated.

## API documentation

### Avoid cache update during upload

Set an additional `X-Postpone-Propagation: true` header on any PUT requests for regular uploads or MKCOL/PUT/MOVE requests for chunked uploading.

Note this will put the filecache into an intermediate state and further user actions should be avoided until finishing the scan user files command.

### Propagate updates to user files


- Scan all user files: `POST /ocs/v2.php/apps/s3scanner/scan/{userId}`
- Scan only a specific path: `POST /ocs/v2.php/apps/s3scanner/scan/{userId}/path/to/directory`

#### Response:
- The response contains space characters before the actual request body in order to avoid loadbalancer timeouts
- Due to the above empty characters the header is already sent so errors in the process is returned by the `ocs.data.status` property

```json
{
  "ocs": {
    "meta": {
      "status": "ok",
      "statuscode": 200,
      "message": "OK"
    },
    "data": {
      "status": "success", // "success" or "error"
      "processed": 10 // number of parent paths processed
    }
  }
}

```
### Example curl requests:

	curl -u admin:admin -H 'OCS-APIRequest: true' -X POST https://nextcloud.local/ocs/v2.php/apps/s3scanner/scan/user1 -v

	curl -u admin:admin -H 'OCS-APIRequest: true' -X POST https://nextcloud.local/ocs/v2.php/apps/s3scanner/scan/user1/path/to/directory -v
