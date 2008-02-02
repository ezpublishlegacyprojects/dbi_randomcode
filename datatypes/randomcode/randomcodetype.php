<?php

define( "EZ_DATATYPE_RANDOMCODE_DIGITS_FIELD", "data_int2" );
define( "EZ_DATATYPE_RANDOMCODE_DIGITS_VARIABLE", "_randomcode_digits_integer_value_" );

include_once( "kernel/classes/ezdatatype.php" );
include_once( "lib/ezutils/classes/ezintegervalidator.php" );

class RandomCodeType extends eZDataType
{
    function RandomCodeType()
    {
        $this->eZDataType( 'randomcode',
                           ezi18n( 'kernel/classes/datatypes', 'Random code', 'Datatype name' ),
                           array( 'serialize_supported' => true,
                                  'object_serialize_map' => array( 'data_text' => 'identifier',
                                                                   'data_int' => 'number' ) ) );
        $this->IntegerValidator = new eZIntegerValidator( 1 );
    }

    function initializeClassAttribute( &$classAttribute )
    {
        if ( $classAttribute->attribute( EZ_DATATYPE_RANDOMCODE_DIGITS_FIELD ) == null )
        {
            $classAttribute->setAttribute( EZ_DATATYPE_RANDOMCODE_DIGITS_FIELD, 1 );
        }
    }

    function validateClassAttributeHTTPInput( &$http, $base, &$classAttribute )
    {
        $digitsName = $base . EZ_DATATYPE_RANDOMCODE_DIGITS_VARIABLE . $classAttribute->attribute( "id" );

        if ( $http->hasPostVariable( $digitsName ) )
        {
            $digitsValue = str_replace( " ", "", $http->postVariable( $digitsName ) );

            $digitsValueState = $this->IntegerValidator->validate( $digitsValue );

            if ( $digitsValueState == EZ_INPUT_VALIDATOR_STATE_ACCEPTED )
            {
                return EZ_INPUT_VALIDATOR_STATE_ACCEPTED;
            }
        }
        return EZ_INPUT_VALIDATOR_STATE_INVALID;
    }

    function fetchClassAttributeHTTPInput( &$http, $base, &$classAttribute )
    {
        $digitsName = $base . EZ_DATATYPE_RANDOMCODE_DIGITS_VARIABLE . $classAttribute->attribute( "id" );

        if ( $http->hasPostVariable( $digitsName ) )
        {
            $digitsValue = str_replace( " ", "", $http->postVariable( $digitsName ) );
            $digitsValue = ( int ) $digitsValue;
            if ( $digitsValue < 1 )
            {
                $digitsValue = 1;
            }

             $classAttribute->setAttribute( EZ_DATATYPE_RANDOMCODE_DIGITS_FIELD, $digitsValue );
        }
        return true;
    }

    function initializeObjectAttribute( &$contentObjectAttribute, $currentVersion, &$originalContentObjectAttribute )
    {
        $contentObjectAttributeID = $originalContentObjectAttribute->attribute( "id" );
        $version = $contentObjectAttribute->attribute( "version" );
        if ( $currentVersion == false )
        {
            $contentObject = $contentObjectAttribute->attribute( 'object' );
            $contentObjectID = $contentObject->attribute( 'id' );
            $contentClassAttribute =& $contentObjectAttribute->attribute( 'contentclass_attribute' );
            $digits = $contentClassAttribute->attribute( EZ_DATATYPE_RANDOMCODE_DIGITS_FIELD );
            $contentClassAttributeID = $contentClassAttribute->attribute( 'id' );
            $max = (int)str_repeat( '9', $digits );
            eZDebug::writeDebug( $max, 'RandomCodeType max value' );

            $db =& eZDB::instance();
            $db->lock( array( array( "table" => "ezcontentobject_attribute" ),
                              array( "table" => "ezcontentclass_attribute" ) ) );

            do {
                $possibleCode = str_pad( rand( 1, $max ), $digits, '0', STR_PAD_LEFT );

                eZDebug::writeDebug( $possibleCode, 'RandomCodeType possible code' );

                $query = "SELECT COUNT(contentobject_id) `count` FROM ezcontentobject_attribute
                          WHERE contentclassattribute_id=$contentClassAttributeID AND
                                sort_key_string='$possibleCode' AND
                                contentobject_id<>$contentObjectID";
                $result = $db->arrayQuery( $query );
                $existingCount = $result[0]['count'];

                eZDebug::writeDebug( 'existing count: ' . $existingCount );
                $isUnique = ( $existingCount == 0 );
            } while ( $isUnique == false );

            $contentObjectAttribute->setAttribute( 'data_text', $possibleCode );
            $contentObjectAttribute->setAttribute( 'sort_key_string', $possibleCode );
            $contentObjectAttribute->store();

            $db->commit();
            $db->unlock();
        }
    }

    function &objectAttributeContent( &$contentObjectAttribute )
    {
        $content = $contentObjectAttribute->attribute( "data_text" );
        return $content;
    }

    function metaData( $contentObjectAttribute )
    {
        return $contentObjectAttribute->attribute( "data_text" );
    }

    function title( &$contentObjectAttribute )
    {
        return  $contentObjectAttribute->attribute( "data_text" );
    }

    function isIndexable()
    {
        return true;
    }

    function sortKey( &$contentObjectAttribute )
    {
        return $contentObjectAttribute->attribute( 'data_text' );
    }

    function sortKeyType()
    {
        return 'string';
    }
}


eZDataType::register( 'randomcode', 'randomcodetype' );

?>