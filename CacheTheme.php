<?php


class CacheTheme extends plxPlugin {
	
	const PREFIXE_CROCHET = "appelCrochet_plxAdmin";
	
	
	public function __construct($default_lang) {
		
		parent::__construct($default_lang);
		
		$crochets = [
			"EditConfiguration",
			"EditProfil",
			"EditUsersUpdate",
			"EditUser",
			"EditCategoriesNew",
			"EditCategoriesUpdate",
			"EditCategorie",
			"EditStatiquesUpdate",
			"EditStatique",
			"EditArticle",
			"DelArticle",
		];
		
		foreach ($crochets as $c) {
			$this->aHooks["plxAdmin$c"][] = [
				"class" => __CLASS__,
				"method" => self::PREFIXE_CROCHET . $c,
			];
		}
		
	}
	
	
	public function __call($name, $arguments) {
		
		if (0 === strpos($name, self::PREFIXE_CROCHET)) {
			
			$crochetActuel = substr($name, strlen(self::PREFIXE_CROCHET));
			
			$baseTheme = self::baseTheme($GLOBALS["plxAdmin"]);
			
			
			// recherche dans les fichiers en cache
			
			foreach (glob("$baseTheme/cache/*.cache") as $filename) {
				
				$contenu = file_get_contents($filename);
				
				// recherche des crochets concernant ce fichier en cache
				preg_match("!<\?php // crochets : (.*)\?>!", $contenu, $resultats);
				
				if (isset($resultats[1])) {
					$listeCrochetsCache = explode("|", $resultats[1]);
					
					if (in_array($crochetActuel, $listeCrochetsCache)) {
						// suppression du fichier de cache à régénérer
						unlink($filename);
						
						break; // passage au fichier suivant
					}
				}
				
			} // FIN foreach (glob("$baseTheme/cache/*.cache") as $filename) {
			
		} // FIN if (0 === strpos($name, self::PREFIXE_CROCHET)) {
		
	} // FIN public function __call($name, $arguments) {
	
	
	
	// méthode statique appelée dans les fichiers du thème
	
	static public function cache(array $arguments) {
		
		$arguments["baseTheme"] = self::baseTheme($GLOBALS["plxMotor"]);
		
		$arguments["fichierCache"] = self::fichierCache($arguments);
		
		
		if (!is_file($arguments["fichierCache"])) {
			self::genererFichierCache($arguments);
		}
		
		require $arguments["fichierCache"];
		
	}
	
	
	
	static private function genererFichierCache(array $arguments) {
		
		global $plxShow, $plxMotor;
		
		// contenu en cache
		
		ob_start();
		require "{$arguments["baseTheme"]}{$arguments["fichier"]}";
		$contenu = ob_get_clean();
		
		
		// crochets
		
		$contenuCache  = "";
		$contenuCache .= "<?php // crochets : {$arguments["crochets"]}?>\n";
		$contenuCache .= "$contenu";
		
		
		// enregistrement
		file_put_contents($arguments["fichierCache"], $contenuCache);
		
	}
	
	
	static private function baseTheme(plxMotor $plxMotor) {
		
		$baseTheme = PLX_ROOT . "{$plxMotor->aConf["racine_themes"]}{$plxMotor->style}/";
		
		return $baseTheme;
		
	}
	
	
	static private function fichierCache(array $arguments) {
		
		$signatureFichier = md5($arguments["fichier"]);
		$codeFichier = preg_replace("![^A-Za-z0-9_-]!", "_", $arguments["fichier"]);
		
		$fichierCache = "{$arguments["baseTheme"]}cache/$codeFichier-$signatureFichier.cache";
		
		return $fichierCache;
		
	}
}

