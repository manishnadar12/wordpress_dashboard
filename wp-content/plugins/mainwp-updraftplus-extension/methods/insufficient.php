<?php
if ( ! defined( 'MAINWP_UPDRAFT_PLUS_DIR' ) ) {
		die( 'No direct access allowed.' ); }

class MainWP_Updraft_Plus_BackupModule_insufficientphp {

	private $required_php;
	private $error_msg;
	private $method;

	public function __construct( $method, $desc, $php, $image = null ) {
			$this->method = $method;
			$this->desc = $desc;
			$this->required_php = $php;
			$this->image = $image;
			$this->error_msg = 'This remote storage method (' . $this->desc . ') requires PHP ' . $this->required_php . ' or later';
			$this->error_msg_trans = sprintf( __( 'This remote storage method (%s) requires PHP %s or later.', 'mainwp-updraftplus-extension' ), $this->desc, $this->required_php );
	}

	private function log_error() {
			global $mainwp_updraftplus;
			$mainwp_updraftplus->log( $this->error_msg );
			$mainwp_updraftplus->log( $this->error_msg_trans, 'error', 'insufficientphp' );
			return false;
	}

		// backup method: takes an array, and shovels them off to the cloud storage
	public function backup( $backup_array ) {

	}

		# $match: a substring to require (tested via strpos() !== false)

	public function listfiles( $match = 'backup_' ) {

	}

		// delete method: takes an array of file names (base name) or a single string, and removes them from the cloud storage
	public function delete( $files, $data = false ) {

	}

		// download method: takes a file name (base name), and brings it back from the cloud storage into Updraft's directory
		// You can register errors with $mainwp_updraftplus->log("my error message", 'error')
	public function download( $file ) {

	}

	private function extra_config() {

	}

		// config_print: prints out table rows for the configuration screen
		// Your rows need to have a class exactly matching your method (in this example, insufficientphp), and also a class of mwp_updraftplusmethod
		// Note that logging is not available from this context; it will do nothing.
	public function config_print() {

			$this->extra_config();
			?>
			<div class="ui grid field mwp_updraftplusmethod <?php echo $this->method; ?>">
                <label class="six wide column middle aligned">
                        <h4 class="ui header">
                         <?php echo htmlspecialchars( $this->desc ); ?>:
                        </h4>
                    </label>
                    <div class="ui ten wide column">
                        <em>
                            <?php echo (( ! empty( $this->image )) ? '<p><img src="' . MAINWP_UPDRAFT_PLUS_URL . '/images/' . $this->image . '"></p>' : ''); ?>
                            <?php echo htmlspecialchars( $this->error_msg_trans ); ?>
                            <?php echo htmlspecialchars( __( 'You will need to ask your web hosting company to upgrade.', 'mainwp-updraftplus-extension' ) ); ?>
                            <?php echo sprintf( __( 'Your %s version: %s.', 'mainwp-updraftplus-extension' ), 'PHP', phpversion() ); ?>
                        </em>
                    </div>
            </div>
        <?php
	}
}
