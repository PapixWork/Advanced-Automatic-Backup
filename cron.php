<?php
class PapixWork
{
	public function Settings()
	{
		// Features | true/false
		$this->enable_db_backup		= true;
		$this->enable_sf_backup		= true;
		$this->enable_discord		= true;

		// General
		$this->api_password			= 'Papix';
		$this->remote_directory_db	= 'papix_backups_db';
		$this->remote_directory_sf	= 'papix_backups_sf';

		$this->local_directory_db	= '/home/papix.work/papix_backups_db';
		$this->local_directory_sf	= '/home/papix.work/papix_backups_sf';

		// SSH (Login)
		$this->ssh_ip				= 'IP_HERE';
		$this->ssh_username			= 'root';
		$this->ssh_password			= 'password';

		// SSH (Database)
		$this->db_host				= "localhost";
		$this->db_username			= "papix";
		$this->db_password			= "123";
		$this->db_names				= array('account', 'common', 'hotbackup', 'log', 'player');

		// SSH (Server Files)
		$this->ssh_sf_directory		= '/usr/home/m2server/server';

		// Discord
		$this->discord_webhook_url	= 'https://discord.com/api/webhooks/';
		$this->discord_message		= 'The automatic backup has been executed successfully.';
	}

	public function Launcher()
	{
		if ($_GET['api_protected'] === $this->api_password)
		{
			include('Net/SSH2.php');
			include('Net/SFTP.php');
			if($this->enable_db_backup	=== true) { $this->BackupDB(); }
			if($this->enable_sf_backup	=== true) { $this->BackupSF(); }
			if($this->enable_discord	=== true) { $this->Discord(); }
			echo("[Papix Work]: Service completed!");
		}
		else
		{
			http_response_code(401);
			echo "[Papix Work]: Wrong API key!";
			exit();
		}
	}

	public function BackupDB()
	{
		$ssh = new Net_SSH2($this->ssh_ip);
		$ssh->login($this->ssh_username, $this->ssh_password) or die("[Papix Work]: SSH Login Failed.");

		$sftp = new Net_SFTP($this->ssh_ip);
		$sftp->login($this->ssh_username, $this->ssh_password) or die("[Papix Work]: SFTP Login Failed.");

		$ssh->enableQuietMode();

		$ssh->exec('cd / && mkdir -p ' . $this->remote_directory_db);

		foreach ($this->db_names as $dbName)
		{
			$backupFile = "/" . $this->remote_directory_db . "/" . $dbName . "_" . date('d-m-Y' . '_'.'H_i_s') . "h.sql.gz";
			$command = "mysqldump -u " . $this->db_username . " -p" . $this->db_password . " -h " . $this->db_host . " " . $dbName . " | gzip -9 > " . $backupFile;
			$ssh->exec($command);
		}

		$dateFolder = date('d-m-Y_H-i-s');
		$ssh->exec('cd /' . $this->remote_directory_db . ' && mkdir ' . $dateFolder);
		$ssh->exec('cd /' . $this->remote_directory_db . ' && mv *.sql.gz ' . $dateFolder);
		$zipFile = "db_" . $dateFolder . ".zip";
		$ssh->exec('cd /' . $this->remote_directory_db . ' && zip -r ' . $zipFile . ' ' . $dateFolder);
		$ssh->exec('cd /' . $this->remote_directory_db . '/' . $dateFolder . ' && rm *.sql.gz');
		$ssh->exec('cd /' . $this->remote_directory_db . ' && rm -rf ' . $dateFolder);

		if (!file_exists($this->local_directory_db)) { mkdir($this->local_directory_db, 0777, true); }
		if ($sftp->get('/' . $this->remote_directory_db . '/' . 'db_'. $dateFolder .'.zip', '/' . $this->local_directory_db . '/' . 'db_'. $dateFolder .'.zip')) { echo ''; } else { echo '[Papix Work]: Error downloading backup of database.<br>'; }

		$ssh->disconnect();
	}

	public function BackupSF()
	{
		$ssh = new Net_SSH2($this->ssh_ip);
		$ssh->login($this->ssh_username, $this->ssh_password) or die("[Papix Work]: SSH Login Failed.");

		$sftp = new Net_SFTP($this->ssh_ip);
		$sftp->login($this->ssh_username, $this->ssh_password) or die("[Papix Work]: SFTP Login Failed.");

		$ssh->enableQuietMode();

		$dateFolder = date('d-m-Y_H-i-s');

		$ssh->exec('cd / && mkdir -p ' . $this->remote_directory_sf);
		$ssh->exec('cd /' . $this->remote_directory_sf .' && tar -zcvf sf_' . $dateFolder . '.tgz /' . $this->ssh_sf_directory);

		if (!file_exists($this->local_directory_sf)) { mkdir($this->local_directory_sf, 0777, true); }
		if ($sftp->get('/' . $this->remote_directory_sf . '/' . 'sf_'. $dateFolder .'.tgz', '/' . $this->local_directory_sf . '/' . 'sf_'. $dateFolder .'.tgz')) { echo ''; } else { echo '[Papix Work]: Error downloading backup of server files.'; }

		$ssh->disconnect();
	}

	public function Discord()
	{
		$data = array('content' => $this->discord_message);
		$json_data = json_encode($data);
		$headers = array('Content-Type: application/json');

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->discord_webhook_url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		curl_close($ch);
	}
}

$start = new PapixWork;
$start->Settings();
$start->Launcher();
?>