<?php

declare(strict_types=1);

/**
 * Foreign_Key definition
 *
 * Extracted from Table_Index from 0.2.*
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @since 0.3.0
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Table_Builder
 */

namespace PinkCrab\Table_Builder;

use PinkCrab\Table_Builder\Index;

class Foreign_Key {

	/**
	 * Index name
	 *
	 * @since 0.1.0
	 * @var string
	 */
	protected $keyname;

	/**
	 * Column referenced
	 *
	 * @since 0.1.0
	 * @var string
	 */
	protected $column;

	/**
	 * Sets the reference column
	 *
	 * @since 0.1.0
	 * @var string|null
	 */
	protected $reference_column;

	/**
	 * The table used.
	 *
	 * @since 0.1.0
	 * @var string|null
	 */
	protected $reference_table;

	/**
	 * Action to execute on update.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	protected $on_update = '';

	/**
	 * Action to execute on delete.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	protected $on_delete = '';

	public function __construct( string $column, ?string $keyname = null ) {
		$this->keyname = $keyname ?? 'fk_' . $column;
		$this->column  = $column;
	}

	/**
	 * Shortcut for definig the reference table and column.
	 *
	 * @param string $reference_table
	 * @param string $reference_column
	 * @return self
	 */
	public function reference( string $reference_table, string $reference_column ): self {
		$this->reference_column = $reference_column;
		$this->reference_table  = $reference_table;
		return $this;
	}

	/**
	 * Set the reference table
	 *
	 * @since 0.1.0
	 * @param string $reference_table
	 * @return self
	 */
	public function reference_table( string $reference_table ): self {
		$this->reference_table = $reference_table;
		return $this;
	}

	/**
	 * Add reference_column to the key.
	 *
	 * @since 0.1.0
	 * @param string $reference_column
	 * @return self
	 */
	public function reference_column( string $reference_column ): self {
		$this->reference_column = $reference_column;
		return $this;
	}

	/**
	 * Sets the on update action.
	 *
	 * @since 0.1.0
	 * @param string $action
	 * @return self
	 */
	public function on_update( string $action ): self {
		$this->on_update = $action;
		return $this;
	}


	/**
	 * Sets the on update action.
	 *
	 * @since 0.1.0
	 * @param string $action
	 * @return self
	 */
	public function on_delete( string $action ): self {
		$this->on_delete = $action;
		return $this;
	}

	/**
	 * Get index name
	 *
	 * @return string
	 */
	public function get_keyname(): string {
		return $this->keyname;
	}

	/**
	 * Get column referenced
	 *
	 * @return string
	 */
	public function get_column(): string {
		return $this->column;
	}

	/**
	 * Get the table used.
	 *
	 * @return string|null
	 */
	public function get_reference_table(): ?string {
		return $this->reference_table;
	}

	/**
	 * Get the table used.
	 *
	 * @return string|null
	 */
	public function get_reference_column(): ?string {
		return $this->reference_column;
	}

	/**
	 * Get action to execute on update.
	 *
	 * @return string
	 */
	public function get_on_update(): string {
		return $this->on_update;
	}

	/**
	 * Get action to execute on delete.
	 *
	 * @return string
	 */
	public function get_on_delete(): string {
		return $this->on_delete;
	}

	/**
	 * Exports the index as a stdClass
	 *
	 * @return object
	 */
	public function export() {
		return (object) array(
			'keyname'          => $this->keyname,
			'column'           => $this->column,
			'reference_column' => $this->reference_column,
			'reference_table'  => $this->reference_table,
			'on_update'        => $this->on_update,
			'on_delete'        => $this->on_delete,
		);
	}
}
