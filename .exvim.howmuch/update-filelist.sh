#!/bin/bash
export DEST="./.exvim.howmuch"
export TOOLS="/root/.vim/tools/"
export IS_EXCLUDE=-not
export FOLDERS="Runtime|ThinkPHP|fonts|img|barcode|audio|uploads|jBox|summernote"
export FILE_SUFFIXS=".*"
export TMP="${DEST}/_files"
export TARGET="${DEST}/files"
export ID_TARGET="${DEST}/idutils-files"
sh ${TOOLS}/shell/bash/update-filelist.sh
