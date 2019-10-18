<?php
/**
 * I know. What else can I do...
 * - Server is running a version of git which is too old to push but not pull...
 * - I don't have sudo access on server
 * - I don't have access to install packages (`$ yum`)
 * - The server is like 20 years EOL on Oracle Linux Server
 * - Only authorized people can push to the remote... So why bother with authorization/authentication...
 */

system("git pull --all");
echo ":]";
