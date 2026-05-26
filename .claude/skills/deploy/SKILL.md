---
name: deploy
description: Deploy the project to Hostinger via FTP by running upload.sh. Use when the user asks to deploy, push to live, or upload changes.
disable-model-invocation: true
---

Run the FTP deployment script to upload files to Hostinger:

```bash
bash upload.sh
```

Before running:
1. Confirm the user wants to deploy to the **live** server (there is no staging environment)
2. Warn if `setup_db.php` still exists in the project root — it should be removed or protected before deploying

After running, report the output and whether the upload succeeded or failed.
