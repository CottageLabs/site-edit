<?php
/*
 * PHP class for handling lookup tables containing an integer key column, a description and optionally a code
 * Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/aeolus/php
 * Author: Sushila Burgess
 *
 */

define( 'MAX_LOOKUP_USES_DISPLAYED', 100 );

require_once 'dbentity.php';

class lookup_table extends DBEntity {

  #------------
  # Properties 
  #------------
  var $max_desc_size = NULL;
  var $id_size       = NULL;
  var $code_size     = NULL;
  var $desc_size     = NULL;

  #-----------------------------------------------------

  function lookup_table( &$db_connection, $lookup_table_name, $id_column_name, $desc_column_name,                          
                         $code_column_name = NULL
                        , $publ_column_name = NULL
                        , $auto_generated_id = TRUE, $fixed_codes = NULL ) { 

    #-----------------------------------------------------
    # Check we have got a valid connection to the database
    #-----------------------------------------------------
    $this->DBEntity( $db_connection );

    if( ! $lookup_table_name ) $this->die_on_error( 'No table name passed into class Lookup Table.' );
    if( ! $id_column_name )    $this->die_on_error( 'No ID column name passed into class Lookup Table.' );
    if( ! $desc_column_name )  $this->die_on_error( 'No description column name passed into class Lookup Table.' );

    $this->lookup_table_name = $lookup_table_name;
    $this->id_column_name    = $id_column_name;
    $this->desc_column_name  = $desc_column_name;
    $this->code_column_name  = $code_column_name;
    $this->auto_generated_id = $auto_generated_id;
    $this->fixed_codes       = $fixed_codes;
    $this->publ_column_name  = $publ_column_name;

    $this->table_contains_code = FALSE;
    if( $this->code_column_name ) {
      $this->table_contains_code = TRUE;
      $this->long_desc_column_name = $this->desc_column_name . '_long';
    }

    if( ! $this->max_desc_size ) $this->max_desc_size = 255;  # can be overridden by child class 
    if( ! $this->id_size       ) $this->id_size       = 6;
    if( ! $this->code_size     ) $this->code_size     = 6;
    if( ! $this->desc_size     ) $this->desc_size     = 70;

    if( $this->debug ) {
      echo "RG 01 lookup_table <br>";
      echo 'lookup_table_name "' . $lookup_table_name . '"' . LINEBREAK ;
      echo 'id_column_name    "' . $id_column_name . '"'    . LINEBREAK ;
      echo 'desc_column_name  "' . $desc_column_name . '"'  . LINEBREAK ;
      echo 'code_column_name  "' . $code_column_name . '"'  . LINEBREAK ;
      echo 'auto_generated_id "' . $auto_generated_id . '"' . LINEBREAK ;
      echo 'fixed_codes       "' . $fixed_codes . '"'       . LINEBREAK ;
      echo 'publ_column_name  "' . $publ_column_name . '"'  . LINEBREAK ;
    }
    /**/
  }
  #-----------------------------------------------------

  function clear( $clear_table_data = TRUE ) {

    if( ! $clear_table_data ) {
      $keep_lookup_table_name = $this->lookup_table_name;
      $keep_id_column_name    = $this->id_column_name;
      $keep_desc_column_name  = $this->desc_column_name;
      $keep_code_column_name  = $this->code_column_name;
      $keep_auto_generated_id = $this->auto_generated_id;
      $keep_fixed_codes       = $this->fixed_codes;
      #--
      $keep_table_contains_code = $this->table_contains_code ;
      $keep_long_desc_column_name = $this->long_desc_column_name ;
      $keep_max_desc_size = $this->max_desc_size ;
      $keep_id_size       = $this->id_size ;
      $keep_code_size     = $this->code_size ;
      $keep_desc_size     = $this->desc_size ;
    }

    parent::clear();

    if( ! $clear_table_data ) {
      $this->lookup_table_name = $keep_lookup_table_name;
      $this->id_column_name    = $keep_id_column_name;
      $this->desc_column_name  = $keep_desc_column_name;
      $this->code_column_name  = $keep_code_column_name;
      $this->auto_generated_id = $keep_auto_generated_id;
      $this->fixed_codes       = $keep_fixed_codes;
      #--
      $this->table_contains_code   = $keep_table_contains_code ;
      $this->long_desc_column_name = $keep_long_desc_column_name ;
      $this->max_desc_size         = $keep_max_desc_size ;
      $this->id_size               = $keep_id_size       ;
      $this->code_size             = $keep_code_size     ;
      $this->desc_size             = $keep_desc_size     ;
    }
  }
  #-----------------------------------------------------

  function get_lookup_list( $called_by = NULL ) {

    $statement = "select $this->id_column_name, ";

    if( $this->table_contains_code ) $statement = $statement . " $this->code_column_name, ";

    $statement = $statement . " $this->desc_column_name ";

    if( $this->table_contains_code ) {
      $statement = $statement . ", $this->code_column_name || '. ' || $this->desc_column_name "
                              . " as $this->long_desc_column_name ";
    }

    $statement = $statement . " from $this->lookup_table_name ";
    $order_by = $this->get_order_by_clause();
    if( ! $order_by ) $order_by = "$this->desc_column_name";

    $statement .= " order by $order_by";
    if( $this->debug ) {
      echo "RG get_lookup_list( <br>";
      var_dump($statement);
    }

    $lookup_list = $this->db_select_into_array( $statement );

    if( ! is_array( $lookup_list )) echo 'No options found.';

    return $lookup_list;
  }
  #-----------------------------------------------------

  function get_order_by_clause() {  # can be overridden by child class

    if( $this->table_contains_code ) 
      $order_by = "$this->code_column_name";
    elseif( ! $this->auto_generated_id )
      $order_by = "$this->id_column_name";
    else
      $order_by = "$this->desc_column_name";

    return $order_by;
  }
  #-----------------------------------------------------

  function lookup_table_dropdown( $field_name = NULL, $field_label = NULL, $selected_id = 0, $in_table = FALSE,
                                  $script = NULL ) {

    if( ! $field_name ) $field_name = $this->id_column_name;

    #--------------------------------------------------------
    # Write out a dropdown list of values from selected table
    #--------------------------------------------------------
    $this->lookup_list = $this->get_lookup_list( $called_by = 'lookup_table_dropdown' );
    if( ! is_array( $this->lookup_list )) return;

    HTML::dropdown_start( $field_name, $field_label, $in_table, $script );

    foreach( $this->lookup_list as $lookup_row ) {

      $display_id = $lookup_row["$this->id_column_name"];
      if( $this->table_contains_code ) 
        $display_desc = $lookup_row["$this->long_desc_column_name"];
      else
        $display_desc = $lookup_row["$this->desc_column_name"];

      HTML::dropdown_option( $display_id,
                             $display_desc,
                             $selected_id );  # selected one
    }
    HTML::dropdown_end( $in_table);
  }
  #----------------------------------------------------- 

  function desc_dropdown( $form_name, $field_name = NULL, $copy_field = NULL, $field_label = NULL,
                          $in_table=FALSE, $override_blank_row_descrip = NULL ) {

    if( ! $form_name ) return NULL; # must be set by calling script
    if( ! $field_name ) $field_name = 'list_desc_' . $this->id_column_name;

    #--------------------------------------------------------
    # Write out a dropdown list of values from selected table
    #--------------------------------------------------------
    $this->lookup_list = $this->get_lookup_list( $called_by = 'desc_dropdown' );
    if( ! is_array( $this->lookup_list )) return;

    $script = NULL;
    if( $copy_field ) {
      $script = 'onchange="var val=' . "$form_name.$field_name.value;" . NEWLINE;
      $script = $script . "var fieldcolour = '" . HTML::get_highlight2_colour( $this->publicly_available_page );
      $script = $script . "';" . NEWLINE;
      $script = $script . "$form_name.$copy_field.value=val;" . NEWLINE;
      $script = $script . "if( val == '' ) fieldcolour='white';" . NEWLINE;
      $script = $script . "$form_name.$copy_field.style.backgroundColor=fieldcolour;";
      $script = $script . '"';
    }

    HTML::dropdown_start( $field_name, $field_label, $in_table, $script );

    $blank_row_descrip = 'Possible values';
    if( trim( $override_blank_row_descrip ) > '' ) $blank_row_descrip = $override_blank_row_descrip;
    HTML::dropdown_option( '', $blank_row_descrip );
    foreach( $this->lookup_list as $lookup_row ) {
      $display_desc = $lookup_row[ "$this->desc_column_name" ];
      HTML::dropdown_option( $display_desc, $display_desc );
    }
    HTML::dropdown_end( $in_table);
  }
  #----------------------------------------------------- 

  function get_lookup_desc( $id_value = 0 ) {

    $statement = "select $this->desc_column_name from $this->lookup_table_name where $this->id_column_name="
               . $id_value;
    $desc = $this->db_select_one_value( $statement );
    return $desc;
  }
  #----------------------------------------------------- 

  function get_lookup_code( $id_value = 0 ) {

    $statement = "select $this->code_column_name from $this->lookup_table_name where $this->id_column_name="
               . $id_value;
    $code = $this->db_select_one_value( $statement );
    return $code;
  }
  #----------------------------------------------------- 

  function get_lookup_id_from_code( $code_value = '' ) {

    $statement = "select $this->id_column_name from $this->lookup_table_name where $this->code_column_name="
               . "'" . $this->escape( $code_value ) . "'";
    $id = $this->db_select_one_value( $statement );
    return $id;
  }
  #----------------------------------------------------- 

  function edit_lookup_table1() {

    $this->class_name = $this->app_get_class($this);

    echo 'You can use this screen to add new records to the closed list, or change existing ones.';
    HTML::new_paragraph();
    
    #-------------------
    # New lookup records
    #-------------------
    HTML::div_start( 'id="new_lookup_record"' );
    HTML::bold_start();
    echo 'Enter new records here:';
    if( $this->debug ) echo "RG edit_lookup_table1() 91<br>";
    HTML::bold_end();
    HTML::new_paragraph();

    $this->form_count = 1;
    $form_name = $this->class_name . '_edit_form' . $this->form_count;
    
    $this->form_name = HTML::form_start( $this->class_name, 'edit_lookup_table2', $form_name,
                       $form_target = '', $onsubmit_validation = TRUE );

    HTML::table_start( 'class="highlight2 contrast1_boxed spacepadded"' );
    HTML::tablerow_start();

    #------------------
    # New row: ID field
    #------------------
    if( ! $this->auto_generated_id ) {   # user selects the ID themselves
      HTML::input_field( $this->id_column_name, 'ID', NULL, $in_table=TRUE, $size=$this->id_size  );
      HTML::new_tablerow();
    }

    $this->write_extra_fields1_new();  # give opportunity for child class to add extra data here by overriding

    #------------------------------------
    # New row: code field (if applicable)
    #------------------------------------
    if( $this->table_contains_code ) {
      HTML::input_field( $this->code_column_name, $this->get_label_for_code_field(), $this->default_code, 
                         $in_table=TRUE, $size=$this->code_size, $tabindex=1, $label_parms='class="rightaligned"');
      HTML::new_tablerow();
    }

    #--------------------------------
    # New row: description field
    #--------------------------------
    HTML::input_field( $this->desc_column_name, $this->get_label_for_desc_field(), '', 
                       $in_table=TRUE, $size=$this->desc_size, $tabindex=1, $label_parms='class="rightaligned"' );
    HTML::new_tablerow();

    $this->write_extra_fields2_new();  # give opportunity for child class to add extra data here by overriding

    HTML::tabledata_start( 'class="rightaligned"');
    HTML::submit_button( 'add_button', 'Add' );
    HTML::tabledata_end();

    HTML::tabledata_start();
    if( $this->auto_generated_id )
      HTML::hidden_field( $this->id_column_name, 0 );  # will tell the "save" function to insert not update
    HTML::tabledata_end();

    HTML::tablerow_end();
    HTML::table_end();
    HTML::form_end();
    HTML::div_end();

    #-----------------
    # Existing records
    #-----------------
    if( $this->debug ) echo "RG edit_lookup_table1() 93<br>";
    HTML::div_start( 'id="existing_lookup_record"' );
    HTML::new_paragraph();

    $existing_lookup = $this->get_lookup_list( $called_by = 'edit_lookup_table1' );
    if( ! is_array( $existing_lookup )) return;

    HTML::bold_start();
    echo 'Change existing records here:';
    HTML::bold_end();
    echo LINEBREAK;
    echo '(You can only change one record at a time.)';
    HTML::new_paragraph();

    foreach( $existing_lookup as $row ) {
      extract( $row, EXTR_OVERWRITE ); # copy into simple variables
      if( $this->debug ) echo "RG edit_lookup_table1() 95<br>";
      if( $this->debug ) var_dump($row);

      $id_column_name = $this->id_column_name;
      $desc_column_name = $this->desc_column_name;
      $code_column_name = $this->code_column_name;
      $publ_column_name = $this->publ_column_name;

      $id_value = $$id_column_name;
      $desc_value = $$desc_column_name;
      $code_value = $$code_column_name;
      $publ_value = $$publ_column_name;

      $this->form_count++;
      $form_name = $this->class_name . '_edit_form' . $this->form_count;

      $this->form_name = HTML::form_start( $this->class_name, 'edit_lookup_table2', $form_name,
                         $form_target = '', $onsubmit_validation = TRUE );

      $anchor = 'id_' . $id_value . '_anchor';
      HTML::anchor( $anchor );

      HTML::hidden_field( $this->id_column_name, $id_value ); #tells "save" function to update not insert

      HTML::table_start( 'class="highlight1 contrast1_boxed spacepadded"' );
      HTML::tablerow_start();

      #--------------------------------
      # Existing row: write readonly ID 
      #--------------------------------
      HTML::tabledata( 'ID ' . $id_value  );
      HTML::tabledata();
      HTML::new_tablerow();

      $this->write_extra_fields1_existing( $id_value );  # let child class add extra data here by overriding

      #---------------------------------------
      # Existing row: write code if applicable
      #---------------------------------------
      if( $this->table_contains_code ) {
        $changeable_code = TRUE;
        if( $this->fixed_codes ) {
          if( is_scalar( $this->fixed_codes )) {
            if( $code_value == $this->fixed_codes ) $changeable_code = FALSE;
          }
          elseif( is_array( $this->fixed_codes )) {
            foreach( $this->fixed_codes as $fixed_code ) {
              if( $code_value == $fixed_code ) {
                $changeable_code = FALSE;
                break;
              }
            }
          }
        }
        if( $changeable_code )
          HTML::input_field( $this->code_column_name, $this->get_label_for_code_field(), $code_value, $in_table=TRUE, 
                             $size=$this->code_size, $tabindex=1, $label_parms='class="rightaligned"'  );
        else {
          HTML::tabledata( $this->get_label_for_code_field() );
          HTML::tabledata( $code_value );
        }
        #HTML::new_tablerow();
      } 
      #-----------------------------------------------------------------
      # Give option to continue at the same point in the form after Save
      #-----------------------------------------------------------------
/*
      HTML::tablerow_start();
      HTML::tabledata('Publish'); # empty cell

      $is_checked = ($publ_value == 1 ) ? TRUE  : FALSE ;
      echo "RG edit_lookup_table1() publ_value == $publ_value <br>";

      HTML::tabledata_start();
      #HTML::italic_start();

      HTML::checkbox( $fieldname = 'publish_status', $label = 'Publish', 
                      $is_checked , $value_when_checked = 1, $in_table = FALSE, $tabindex = 1,
                      $input_instance = $id_value, $label_on_left = TRUE );
      HTML::tabledata_end();
*/
      HTML::tablerow_end();

      #--------------------------------
      # Existing row: write description
      #--------------------------------
      HTML::input_field( $this->desc_column_name, $this->get_label_for_desc_field(), $desc_value, $in_table=TRUE, 
                         $size=$this->desc_size, $tabindex=1, $label_parms='class="rightaligned"' );

      #-----------------------------------------------------------------
      # Give option to continue at the same point in the form after Save
      #-----------------------------------------------------------------
      HTML::tablerow_start();
      HTML::tabledata('Publish'); # empty cell

      $is_checked = ($publ_value == 1 ) ? TRUE  : FALSE ;
      if( $this->debug ) echo "RG2 edit_lookup_table1() publ_value == $publ_value <br>";

      HTML::tabledata_start();
      #HTML::italic_start();

      HTML::checkbox( $fieldname = 'publish_status', $label = '', 
                      $is_checked , $value_when_checked = 1, $in_table = FALSE, $tabindex = 1,
                      $input_instance = $id_value, $label_on_left = TRUE );
      HTML::tabledata_end();
      HTML::tablerow_end();

      HTML::new_tablerow();

      $this->write_extra_fields2_existing( $id_value );  # let child class add extra data here by overriding

      HTML::tabledata_start( 'class="rightaligned"' );
      HTML::submit_button( 'change_button', $this->get_label_for_change_button() );
      HTML::tabledata_end();

      #.................................................................

      HTML::tabledata_start( 'class="rightaligned"' );

      HTML::hidden_field( $this->id_column_name, $id_value );
      if( $this->table_contains_code && ! $changeable_code )
        HTML::hidden_field( $this->code_column_name, $code_value );

      HTML::italic_start();
      echo 'Check whether deletion is currently possible:';
      HTML::italic_end();
      HTML::submit_button( 'check_deletion_button', 'Check' );

      HTML::tabledata_end();
      HTML::tablerow_end();

      #-----------------------------------------------------------------
      # Give option to continue at the same point in the form after Save
      #-----------------------------------------------------------------
      HTML::tablerow_start();
      HTML::tabledata(); # empty cell

      HTML::tabledata_start();
      HTML::italic_start();

      HTML::checkbox( $fieldname = 'existing_record_anchor', $label = 'Continue here after saving', 
                      $is_checked = TRUE, $value_when_checked = $anchor, $in_table = FALSE, $tabindex = 1,
                      $input_instance = $id_value );

      HTML::span_start( 'class="widespaceonleft"' );
      HTML::link_to_page_top();
      HTML::span_end();

      HTML::italic_end();
      HTML::tabledata_end();
      HTML::tablerow_end();

      HTML::table_end();

      HTML::form_end();
      HTML::new_paragraph();
    }
    HTML::div_end();

    if( $this->parm_found_in_post( 'existing_record_anchor' )) {
      $anchor = $this->read_post_parm( 'existing_record_anchor' );
      HTML::write_javascript_function( 'window.location.hash = "' . $anchor . '"' );
    }
  }
  #----------------------------------------------------- 

  function write_extra_fields1_new() { # override this in the child method if required
    if( $this->debug ) echo "RG write_extra_fields1_new()<br>";
    return NULL;
  }
  #----------------------------------------------------- 

  function write_extra_fields2_new() { # override this in the child method if required
    if( $this->debug ) echo "RG write_extra_fields2_new()<br>";
    return NULL;
  }
  #----------------------------------------------------- 

  function write_extra_fields1_existing( $id_value = NULL ) { # override this in the child method if required
//echo "RG write_extra_fields1_existing( $id_value = NULL )<br>";
    return NULL;
  }
  #----------------------------------------------------- 

  function write_extra_fields2_existing( $id_value = NULL ) { # override this in the child method if required
//echo "RG write_extra_fields2_existing( $id_value = NULL )<br>";
    return NULL;
  }
  #----------------------------------------------------- 

  function get_extra_insert_cols() { # override if required
    if( $this->debug ) echo "RG get_extra_insert_cols()<br>";
    return NULL;
  }
  #----------------------------------------------------- 

  function get_extra_insert_vals() { # override if required
    if( $this->debug ) echo "RG get_extra_insert_vals()<br>";
    return NULL;
  }
  #----------------------------------------------------- 

  function get_extra_update_cols_and_vals() { # override if required
    return NULL;
  }
  #----------------------------------------------------- 

  function edit_lookup_table2() {
    if( $this->debug ) echo "RG edit_lookup_table2()<br>";

    if( $this->parm_found_in_post( 'check_deletion_button' )) {
      $this->check_lookup_deletion();
      return;
    }

    elseif( $this->parm_found_in_post( 'delete_button' )) {
      $this->delete_lookup_record();
      return;
    }

    elseif( $this->parm_found_in_post( 'cancel_deletion_button' )) {
      HTML::italic_start();
      echo 'Deletion cancelled.';
      HTML::italic_end();
      HTML::new_paragraph();
      $this->edit_lookup_table1();
      return;
    }

    $this->failed_validation = FALSE;
    $this->continue_on_read_parm_err = TRUE;

    $id_val   = $this->read_post_parm( $this->id_column_name );
    $desc_val = $this->read_post_parm( $this->desc_column_name );
    if( $this->debug ) echo "RG edit_lookup_table2() 99b <br> ".$this->publ_column_name;

    $publ_val = ( $_POST[ $this->publ_column_name ] == 1) ? 1 : 0 ;
    //$publ_val = $this->read_post_parm( $this->publ_column_name );
    if( $this->debug ) echo "RG edit_lookup_table2() 99c.  $publ_val<br> ";
    if( $this->table_contains_code ) $code_val = $this->read_post_parm( $this->code_column_name );

    $code_val = $this->escape( trim( strtoupper( $code_val )));
    $desc_val = $this->escape( trim( $desc_val ));

    $this->validate_input( $id_val, $desc_val, $code_val );
    if( $this->failed_validation ) {
      $this->edit_lookup_table1();
      return;
    }

    $new_record = FALSE;
    if( ! $id_val )
      $new_record = TRUE;
    elseif( ! $this->auto_generated_id ) {
      $statement = "select count(*) from $this->lookup_table_name where $this->id_column_name = $id_val";
      $exists = $this->db_select_one_value( $statement );
      if( ! $exists ) $new_record = TRUE;
    }

    if( ! $new_record ) {
      $descrip = 'existing';

      $statement = "update $this->lookup_table_name set ";

      if( $this->table_contains_code ) 
        $statement = $statement . " $this->code_column_name = '$code_val', ";
        $statement = $statement . " $this->publ_column_name = '$publ_val', ";

      $statement .= $this->get_extra_update_cols_and_vals(); # may be overridden by child method

      $statement = $statement . " $this->desc_column_name = '$desc_val' where $this->id_column_name = $id_val";
    }
    else {
      $descrip = 'new';
      $statement = "insert into $this->lookup_table_name ( ";
      if( ! $this->auto_generated_id ) $statement = $statement . $this->id_column_name . ', ';
      if( $this->table_contains_code ) $statement = $statement . $this->code_column_name . ', ';
      $statement = $statement . $this->get_extra_insert_cols();
      $statement = $statement . $this->desc_column_name;

      $statement = $statement . ' ) values ( ';
      if( ! $this->auto_generated_id ) $statement = $statement . $id_val . ', ';
      if( $this->table_contains_code ) $statement = $statement . "'" . $code_val . "', ";
      $statement = $statement . $this->get_extra_insert_vals();
      $statement = $statement . "'" . $desc_val . "')";
    }
    if( $this->debug ) echo "RG edit_lookup_table2() executing<br>$statement<br> ";
    $this->db_run_query( $statement );

    HTML::italic_start();
    $code_val = $this->strip_all_slashes( $code_val );
    $desc_val = $this->strip_all_slashes( $desc_val );
    echo "Saved $descrip record: ";
    if( $id_val ) echo "ID $id_val: ";
    $this->echo_safely( "$code_val $desc_val" );
    HTML::italic_end();
    HTML::new_paragraph();

    $this->edit_lookup_table1();
  }
  #----------------------------------------------------- 

  function delete_lookup_record() {

    if( ! $this->parm_found_in_post( 'delete_button' )) 
      $this->die_on_error( 'Delete button was not pressed.' );

    $id_val = $this->read_post_parm( $this->id_column_name );
    if( ! $id_val ) $this->die_on_error( 'No ID value found.' );

    $desc = $this->get_lookup_desc( $id_val );

    $statement = "delete from $this->lookup_table_name where $this->id_column_name = $id_val";
    $this->db_run_query( $statement );

    HTML::italic_start();
    $this->echo_safely( 'Deleted ID ' . $id_val . ' (' . $desc . ')' );
    HTML::italic_end();
    HTML::new_paragraph();

    $this->edit_lookup_table1();
  }
  #----------------------------------------------------- 

  function validate_input( $id_val, $desc_val, $code_val = NULL ) {

    #----------------------------------
    # Check description is not too long
    #----------------------------------
    if( strlen( $desc_val ) > $this->max_desc_size ) {
      $this->failed_validation = TRUE;
      $this->display_errmsg( 'Description', "Value '" . $this->strip_all_slashes($desc_val )
                             . "' (" . strlen($desc_val) . ' characters) exceeds maximum valid length (' 
                             . $this->max_desc_size . ' characters)');
    }

    #------------------------------------
    # Check description is not duplicated
    #------------------------------------
    $statement = "select $this->id_column_name from $this->lookup_table_name where ";
    if( $id_val ) $statement = $statement . " $this->id_column_name != $id_val and ";
    $statement = $statement . " trim(lower( $this->desc_column_name )) = '" 
                            . trim(strtolower($this->escape( $desc_val ))) . "'";
    $other_id = $this->db_select_one_value( $statement );
    if( $other_id ) {
      $this->failed_validation = TRUE;
      $this->display_errmsg( 'Description', "Value '" . $this->strip_all_slashes($desc_val )
                           . "' already exists in the table (ID $other_id) and cannot be duplicated." );
    }

    #-----------------------------
    # Check code is not duplicated
    #-----------------------------
    if( $this->table_contains_code ) {
      $statement = "select $this->id_column_name from $this->lookup_table_name where ";
      if( $id_val ) $statement = $statement . " $this->id_column_name != $id_val and ";
      $statement = $statement . " trim(lower( $this->code_column_name )) = '" 
                              . trim(strtolower($this->escape( $code_val ))) . "'";
      $other_id = $this->db_select_one_value( $statement );
      if( $other_id ) {
        $this->failed_validation = TRUE;
        $this->display_errmsg( 'Code', "Value '" . $this->strip_all_slashes($code_val )
                             . "' already exists in the table (ID $other_id) and cannot be duplicated." );
      }
    }
  }
  #----------------------------------------------------- 

  function check_lookup_deletion( $id_value = NULL ) {

    if( ! $id_value ) $id_value = $this->read_post_parm( $this->id_column_name );
    if( ! $id_value ) return;

    $class_name = $this->app_get_class($this);

    HTML::italic_start();
    echo 'Check whether record can be deleted:';
    HTML::italic_end();
    HTML::new_paragraph();
    HTML::bold_start();

    echo 'ID ' . $id_value;
    HTML::new_paragraph();
    if( $this->table_contains_code ) {
      $this->echo_safely( 'Code: ' . $this->get_lookup_code( $id_value ));
      HTML::new_paragraph();
    }

    $this->echo_safely( 'Description: ' . $this->get_lookup_desc( $id_value ));

    HTML::bold_end();
    HTML::new_paragraph();

    $uses = $this->find_uses_of_this_id( $id_value );

    if( ! $uses ) {
      HTML::form_start( $class_name, 'edit_lookup_table2' );
      HTML::new_paragraph();
      echo 'It should be possible to delete this record, as it does not appear to be used elsewhere in the data.';
      HTML::new_paragraph();
      HTML::submit_button( 'delete_button', 'Delete' );
      echo ' ';
      HTML::submit_button( 'cancel_deletion_button', 'Cancel' );
      HTML::hidden_field( $this->id_column_name, $id_value );
      HTML::form_end();
    }
    else {  # ID is in use
      $can_edit_referencing_data = FALSE;
      if( $this->referencing_class && $this->referencing_method && $this->referencing_id_column )
        $can_edit_referencing_data = TRUE;

      HTML::form_start( $class_name, 'edit_lookup_table2' );
      HTML::new_paragraph();
      echo 'This record is in use within the database, so cannot be deleted until all references '
           . ' to it have been removed.';
      HTML::new_paragraph();

      if( $can_edit_referencing_data ) {
        echo 'Click the Edit button below to open a new browser tab or window allowing you to edit the data '
             . ' and remove references to the lookup record which you wish to delete. ';
        HTML::new_paragraph();

        echo 'After removing the reference to this lookup record, simply close the newly-opened tab/window '
             . ' and click the Refresh button in the current one.';
        HTML::new_paragraph();
      }


      HTML::submit_button( 'check_deletion_button', 'Refresh' );
      echo ' ';
      HTML::submit_button( 'cancel_deletion_button', 'Cancel' );
      HTML::hidden_field( $this->id_column_name, $id_value );
      HTML::form_end();
      HTML::new_paragraph();
      
      if( is_array( $uses )) {

        $uses = $this->limit_display_of_lookup_uses( $uses );

        $first_row = TRUE;
        HTML::table_start( 'class="datatab spacepadded"' );
        foreach( $uses as $row ) {
          if( $first_row ) {
            $first_row = FALSE;
            if( is_array( $this->lookup_reference_column_labels )) {
              HTML::tablerow_start();
              foreach( $row as $colname => $colvalue ) {
                HTML::column_header( $this->lookup_reference_column_labels[ "$colname" ] );
              }
              HTML::tablerow_end();
            }
          }
          HTML::tablerow_start();
          foreach( $row as $colname => $colvalue ) {

            # If enough details have been supplied, allow editing of the data which references this lookup record
            if( $can_edit_referencing_data && $colname ==  $this->referencing_id_column ) {
              HTML::tabledata_start( 'class="highlight1"' );

              HTML::form_start( $this->referencing_class, $this->referencing_method, 
                                $form_name = '', # name will be auto-generated 
                                $form_target = '_blank' );
              if( ! $this->hide_referencing_id_column )
                echo $colvalue . LINEBREAK;
              HTML::hidden_field( $colname, $colvalue );

              #---------------------------------------------------------------------------------------------
              # Initial step in enabling the called class to put up a message saying:
              # 'Changes have been saved. You may now like to close this tab', followed by a 'Close' button.
              # However, called class will have to explicitly call method copy_opening_class_and_method()
              # in its 'Save' form before the message/close button are fully enabled.
              #---------------------------------------------------------------------------------------------
              HTML::hidden_field( 'opening_class',  $this->app_get_class( $this ));
              HTML::hidden_field( 'opening_method', 'check_lookup_deletion' );

              HTML::submit_button( 'edit_button', 'Edit' );
              HTML::form_end();
              HTML::tabledata_end();
            }

            else
              HTML::tabledata( $colvalue );
          }
          HTML::tablerow_end();
        }
        HTML::table_end();
      }
    }
    HTML::new_paragraph();
  }
  #----------------------------------------------------- 

  function find_uses_of_this_id( $id_value = NULL ) {  # this should be overwritten by child class

    return NULL;
  }
  #----------------------------------------------------- 

  function limit_display_of_lookup_uses( $uses ) {

    $usage_count = count( $uses );
    if( $usage_count <= MAX_LOOKUP_USES_DISPLAYED ) return $uses;

    $limited_uses = array();
    for( $i = 0; $i < MAX_LOOKUP_USES_DISPLAYED; $i++ ) {
      $one_use = array_shift( $uses );
      $limited_uses[] = $one_use;
    }
    $uses = NULL;

    HTML::italic_start();
    echo '(This record is used in more than ' . MAX_LOOKUP_USES_DISPLAYED . ' places in the database. ';
    echo 'Only the first ' . MAX_LOOKUP_USES_DISPLAYED . ' uses are being displayed here.)';
    HTML::italic_end();
    HTML::new_paragraph();

    return $limited_uses;
  }
  #-----------------------------------------------------

  function get_label_for_code_field() { # override this in the child method if required
    return 'Code';
  }
  #----------------------------------------------------- 

  function get_label_for_desc_field() { # override this in the child method if required
    return 'Description';
  }
  #----------------------------------------------------- 

  function get_label_for_change_button() { # override this in the child method if required
    return 'Change';
  }
  #----------------------------------------------------- 

  function validate_parm( $parm_name ) {  # overrides parent method

    if( $this->debug ) echo "RG validate_parm( $parm_name ) lookup_table<br> ";

    switch( $parm_name ) {

      case $this->id_column_name:
        return $this->is_integer( $this->parm_value );

      case $this->code_column_name:
        return $this->is_alphanumeric( trim( $this->parm_value ));

      case 'existing_record_anchor':
      case $this->publ_column_name:
        return $this->is_alphanumeric( trim( $this->parm_value ), $allow_underscores = TRUE );

      case $this->desc_column_name:
        return $this->is_ok_free_text( $this->parm_value );

      default:
        return parent::validate_parm( $parm_name );
    }
  }
  #----------------------------------------------------- 
}
?>
