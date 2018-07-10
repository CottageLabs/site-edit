#! /bin/bash
echo 'Copying CSV files to front end server.'

# Check for paramater $1
if [ ! -v 1 ] || [ -z "$1" ]
then
	echo "First parameter not set to csv source directory"
	exit 1
fi

# Check for parameter $2
if [ ! -v 2 ] || [ -z "$2" ]
then
	echo "Second parameter not set to server access string, e.g. user@server"
	exit 1
fi


csv_source=$1
server_access=$2

# New server data transfers
folder_location=/data/emlo-docker-compose/data/
remote_location=${server_access}:${folder_location}

for objects in manifestation comment image institution location person relationship resource work pro_activity pro_assertion pro_location pro_primary_person pro_relationship pro_role_in_activity pro_textual_source
do
	csv_local_file=${csv_source}cofk_union_${objects}.csv

	csv_remote_file=${remote_location}${objects}.csv
	echo "Export to $csv_remote_file"
	rsync -zqt ${csv_local_file} ${csv_remote_file}
done

# When all csv files are done update a flag
ssh ${server_access} 'echo 1 > '${folder_location}'need_index'

#---------------------------------------------------------------------------------------



