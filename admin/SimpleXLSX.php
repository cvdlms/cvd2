<?php
/**
 * SimpleXLSX php class v0.8.19
 * MS Excel 2007 / 2010 / 2013 / 2016 reader
 *
 * Website: http://www.phpclasses.org/package/6279-PHP-Parse-and-retrieve-data-from-Excel-XLS-files.html
 * License: MIT License
 *
 * Authors:
 *   Sergey Schuchkin <sergey.schuchkin@gmail.com>
 *   Vadim Janzen <vadimj@gmail.com>
 */

class SimpleXLSX {
    // Don't remove this string! Created by Sergey Schuchkin from http://www.sibvision.ru - professional php developers team 2012-2015
    public static $CF = array( // Cell formats
        0  => 'General',
        1  => '0',
        2  => '0.00',
        3  => '#,##0',
        4  => '#,##0.00',
        9  => '0%',
        10 => '0.00%',
        11 => '0.00E+00',
        12 => '# ?/?',
        13 => '# ??/??',
        14 => 'mm-dd-yy',
        15 => 'd-mmm-yy',
        16 => 'd-mmm',
        17 => 'mmm-yy',
        18 => 'h:mm AM/PM',
        19 => 'h:mm:ss AM/PM',
        20 => 'h:mm',
        21 => 'h:mm:ss',
        22 => 'm/d/yy h:mm',

        37 => '#,##0 ;(#,##0)',
        38 => '#,##0 ;[Red](#,##0)',
        39 => '#,##0.00;(#,##0.00)',
        40 => '#,##0.00;[Red](#,##0.00)',

        44 => '_("$"* #,##0.00_);_("$"* \(#,##0.00\);_("$"* "-"??_);_(@_)',
        45 => 'mm:ss',
        46 => '[h]:mm:ss',
        47 => 'mmss.0',
        48 => '##0.0E+0',
        49 => '@',

        27 => '[$-404]e/m/d',
        30 => 'm/d/yy',
        36 => '[$-404]e/m/d',
        50 => '[$-404]e/m/d',
        57 => '[$-404]e/m/d',

        59 => 't0',
        60 => 't0.00',
        61 => 't#,##0',
        62 => 't#,##0.00',
        67 => 't0%',
        68 => 't0.00%',
        69 => 't0.00E+00',
        70 => 't# ?/?'
    );

    public $nf = array(); // number formats
    public $cellFormats = array(); // cellXfs
    public $datetimeFormat = 'Y-m-d H:i:s';
    public $debug = false;
    public $activeSheet = 0;
    public $rowsExReader = false;

    private $sheets = array();
    private $hyperlinks = array();
    private $package = array(
        "filename" => "",
        "mtime"    => 0,
        "size"     => 0,
        "comment"  => "",
        "entries"  => array()
    );
    private $sharedstrings = array();
    private $styles = array();

    // scheme
    const SCHEMA_REL_OFFICEDOCUMENT  =  "http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument";
    const SCHEMA_REL_SHAREDSTRINGS   =  "http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings";
    const SCHEMA_REL_WORKSHEET       =  "http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet";
    const SCHEMA_REL_STYLES          =  "http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles";

    private $workbook_cell_formats = array();
    private $built_in_cell_formats = array(
        0  => 'General',
        1  => '0',
        2  => '0.00',
        3  => '#,##0',
        4  => '#,##0.00',
        5  => '$#,##0;($#,##0)',
        6  => '$#,##0;[Red]($#,##0)',
        7  => '$#,##0.00;($#,##0.00)',
        8  => '$#,##0.00;[Red]($#,##0.00)',
        9  => '0%',
        10 => '0.00%',
        11 => '0.00E+00',
        12 => '# ?/?',
        13 => '# ??/??',
        14 => 'm/d/yyyy', // Despite ECMA 'mm-dd-yy'
        15 => 'd-mmm-yyyy',
        16 => 'd-mmm',
        17 => 'mmm-yyyy',
        18 => 'h:mm AM/PM',
        19 => 'h:mm:ss AM/PM',
        20 => 'h:mm',
        21 => 'h:mm:ss',
        22 => 'm/d/yyyy h:mm', // Despite ECMA 'm/d/yy h:mm'

        37 => '#,##0 ;(#,##0)', // Despite ECMA '#,##0 ;(#,##0)'
        38 => '#,##0 ;[Red](#,##0)', // Despite ECMA '#,##0 ;[Red](#,##0)'
        39 => '#,##0.00;(#,##0.00)', // Despite ECMA '#,##0.00;(#,##0.00)'
        40 => '#,##0.00;[Red](#,##0.00)', // Despite ECMA '#,##0.00;[Red](#,##0.00)'

        41 => '_(* #,##0_);_(* \(#,##0\);_(* "-"_);_(@_)',
        42 => '_($* #,##0_);_($* \(#,##0\);_($* "-"_);_(@_)',
        43 => '_(* #,##0.00_);_(* \(#,##0.00\);_(* "-"??_);_(@_)',
        44 => '_($* #,##0.00_);_($* \(#,##0.00\);_($* "-"??_);_(@_)',
        45 => 'mm:ss',
        46 => '[h]:mm:ss',
        47 => 'mm:ss.0',
        48 => '##0.0E+0',
        49 => '@',
    );

    function __construct( $filename = false, $is_data = false, $debug = false ) {
        $this->debug = $debug;
        $this->parse( $filename, $is_data );
    }

    // Parse data
    public function parse( $filename, $is_data = false ) {
        $this->package = array(
            "filename" => "",
            "mtime"    => 0,
            "size"     => 0,
            "comment"  => "",
            "entries"  => array()
        );
        $this->sheets = array();
        $this->hyperlinks = array();
        $this->sharedstrings = array();
        $this->styles = array();

        if ( $filename && $is_data ) {
            $this->package["filename"] = "default.xlsx";
            $this->package["mtime"]   = time();
            $this->package["size"]    = strlen( $filename );

            $vZ = $filename;
        } else {
            if ( !is_readable( $filename ) ) {
                $this->error( 'File not found ' . $filename );

                return false;
            }

            // Package information
            $this->package["filename"] = $filename;
            $this->package["mtime"]    = filemtime( $filename );
            $this->package["size"]     = filesize( $filename );

            // Read file
            $vZ = file_get_contents( $filename );
        }

        // Unzip
        $aE = explode( "\x50\x4b\x05\x06", $vZ );

        // Cut end of central directory
        $aP = unpack( 'x16/v1CL', $aE[1] );
        $this->package['comment'] = substr( $aE[1], 18, $aP['CL'] );

        // Entries
        $aE = explode( "\x50\x4b\x01\x02", $vZ );
        $aE = explode( "\x50\x4b\x03\x04", $aE[0] );
        array_shift( $aE );

        foreach ( $aE as $pK ) {
            $aI = array();
            $aI['E']  = 0;
            $aI['EM'] = '';
            // Cut directory entries
            $aP = unpack( 'v1VN/v1GPF/v1CM/v1FT/v1FD/V1CRC/V1CS/V1UCS/v1FNL', $pK );
            $bE = $aP['FNL'];
            $nF = $aP['VN'];
            // Archive extra data
            if ( $aP['GPF'] & 0x0008 ) {
                $bE += 4;
            }
            // Long file name
            if ( $aP['GPF'] & 0x0400 ) {
                $nF = $aP['FNL'];
            }
            // File name
            $aI['N'] = substr( $pK, 42, $bE );
            $bE += 42;

            // Cut entries
            $pK = substr( $pK, $bE );

            if ( strlen( $pK ) != $aP['CS'] ) {
                $aI['E'] = 1;
                $aI['EM'] = 'Compressed size is not equal with the value in header information.';
            } else {
                if ( $aP['CM'] == 0 ) { // Stored
                    // Here is nothing!
                } elseif ( $aP['CM'] == 8 ) { // Deflated
                    $pK = gzinflate( $pK );
                } elseif ( $aP['CM'] == 12 ) { // BZIP2
                    if ( extension_loaded( 'bz2' ) ) {
                        $pK = bzdecompress( $pK );
                    } else {
                        $aI['E'] = 1;
                        $aI['EM'] = 'BZIP2 extension not available.';
                    }
                } else {
                    $aI['E'] = 1;
                    $aI['EM'] = 'Compression method ' . $aP['CM'] . ' is not supported.';
                }

                if ( ! $aI['E'] ) {
                    $aI['D'] = $pK;
                }
            }

            $aI['T'] = mktime( ($aP['FT']  & 0xf800) >> 11,
                              ($aP['FT']  & 0x07e0) >>  5,
                               $aP['FT']  & 0x001f,
                              ($aP['FD']  & 0xf800) >> 11,
                              ($aP['FD']  & 0x07e0) >>  5,
                              (($aP['FD'] & 0x001f) + 1980) );
            $this->package['entries'][] = array(
                'data'      => $aI['D'],
                'error'     => $aI['E'],
                'error_msg' => $aI['EM'],
                'name'      => $aI['N'],
                'time'      => $aI['T'],
                'size'      => $aP['UCS']
            );
        } // foreach

        $this->parsePackage();
    }

    private function parsePackage() {

        // Document data holders
        $aE = array();

        // Read entries
        foreach ( $this->package['entries'] as $entry ) {

            if ( substr( $entry['name'], 0, 1 ) == '/' || strpos( $entry['name'], '\\' ) !== false ) {
                continue;
            }

            if ( $entry['error'] ) {
                $this->error( $entry['error_msg'] );
                return false;
            }

            // Workbook directory
            if ( strpos( $entry['name'], 'xl/' ) === 0 ) {

                // Read relations and search for officeDocument
                if ( $entry['name'] == 'xl/_rels/workbook.xml.rels' ) {
                    $data = simplexml_load_string( $entry['data'] );
                    if ( $data->Relationship ) {
                        foreach ( $data->Relationship as $rel ) {
                            $key = basename( $rel['Target'] );
                            switch ( $rel['Type'] ) {
                                case self::SCHEMA_REL_SHAREDSTRINGS:
                                    $this->sharedstrings['key'] = $key;
                                    break;
                                case self::SCHEMA_REL_STYLES:
                                    $this->styles['key'] = $key;
                                    break;
                                case self::SCHEMA_REL_WORKSHEET:
                                    $this->sheets[ str_replace( 'sheet', '', $key ) ]['key'] = $key;
                                    break;
                            }
                        }
                    }
                } elseif ( $entry['name'] == 'xl/workbook.xml' ) {
                    $data = simplexml_load_string( $entry['data'] );

                    if ( $data->sheets->sheet ) {
                        $s = array();
                        foreach ( $data->sheets->sheet as $sheet ) {
                            $s[(string)$sheet['sheetId']] = array(
                                'name' => (string)$sheet['name'],
                                'id'   => (int)$sheet['sheetId']
                            );
                        }
                        ksort( $s );
                        $this->sheets = $s;
                    }
                } elseif ( strpos( $entry['name'], 'xl/worksheets/sheet' ) === 0 ) {
                    $sheet = str_replace( array('xl/worksheets/sheet', '.xml'), '', $entry['name'] );
                    $data = simplexml_load_string( $entry['data'] );
                    $this->sheets[$sheet]['data'] = $data;
                } elseif ( $entry['name'] == 'xl/sharedStrings.xml' ) {
                    $data = simplexml_load_string( $entry['data'] );
                    if ( isset( $data->si ) ) {
                        foreach ( $data->si as $val ) {
                            if ( isset( $val->t ) ) {
                                $this->sharedstrings[] = (string)$val->t;
                            } elseif ( isset( $val->r ) ) {
                                $this->sharedstrings[] = $this->parseRichText( $val );
                            } else {
                                $this->sharedstrings[] = '';
                            }
                        }
                    }
                } elseif ( $entry['name'] == 'xl/styles.xml' ) {
                    $this->parseStyles( $entry['data'] );
                }
            }
        } // foreach

        // Sort sheets
        ksort( $this->sheets );
    }

    private function parseStyles( $data ) {
        $xml = simplexml_load_string( $data );

        // Number formats
        $this->nf = array();
        if ( isset( $xml->numFmts ) && $xml->numFmts->numFmt ) {
            foreach ( $xml->numFmts->numFmt as $v ) {
                $this->nf[ (int)$v['numFmtId'] ] = (string)$v['formatCode'];
            }
        }

        // Cell formats
        $this->cellFormats = array();
        if ( isset( $xml->cellXfs ) && $xml->cellXfs->xf ) {
            foreach ( $xml->cellXfs->xf as $v ) {
                $xfi = (int)$v['numFmtId'];
                // 0-49 build-in, 50+ user-defined
                if ( $xfi >= 50 && isset( $this->nf[$xfi] ) ) {
                    $item = $this->nf[$xfi];
                } elseif ( isset( self::$CF[$xfi] ) ) {
                    $item = self::$CF[$xfi];
                } else {
                    $item = self::$CF[0];
                }
                $this->cellFormats[] = $item;
            }
        }
    }

    private function parseRichText( $is ) {
        $value = array();

        if ( isset( $is->t ) ) {
            $value[] = (string)$is->t;
        } else {
            foreach ( $is->r as $run ) {
                $value[] = (string)$run->t;
            }
        }

        return implode( ' ', $value );
    }

    public function error( $set = false ) {
        static $error = false;
        return $set ? $error = $set : $error;
    }

    public function rows( $worksheetIndex = 0 ) {

        if ( ( $ws = $this->worksheet( $worksheetIndex ) ) === false ) {
            return false;
        }
        $wsData = $this->sheets[$worksheetIndex]['data'];

        $this->rowsExReader = false;
        $rows = array();
        if ( isset( $wsData->sheetData->row ) ) {
            foreach ( $wsData->sheetData->row as $row ) {
                $rows[(int)$row['r']] = $this->parseRow( $row );
            }
        }

        return $rows;
    }

    public function rowsEx( $worksheetIndex = 0 ) {

        if ( ( $ws = $this->worksheet( $worksheetIndex ) ) === false ) {
            return false;
        }
        $wsData = $this->sheets[$worksheetIndex]['data'];

        $this->rowsExReader = true;
        $rows = array();
        if ( isset( $wsData->sheetData->row ) ) {
            foreach ( $wsData->sheetData->row as $row ) {
                $rows[(int)$row['r']] = $this->parseRow( $row );
            }
        }

        return $rows;
    }

    private function parseRow( $row ) {
        $cells = array();

        foreach ( $row->c as $c ) {
            $r = (string)$c['r'];
            $cell = array(
                'type'   => (string)$c['t'],
                'value'  => '',
                'href'   => '',
                'f'      => '',
                'format' => '',
                'r'      => $r
            );

            $cell['index'] = $this->parseCellAddr( $r );

            if ( isset( $c['s'] ) ) {
                $cell['format'] = $this->cellFormats[ (int)$c['s'] ];
            }

            if ( isset( $c->f ) ) {
                $cell['f'] = (string)$c->f;
                // TODO: show as text '=('.$c->f.')';
            }

            if ( isset( $c->v ) ) {
                if ( $c['t'] == 's' ) {
                    $cell['value'] = $this->sharedstrings[ (int)$c->v ];
                } else {
                    $cell['value'] = (string)$c->v;

                    if ( strpos( $cell['value'], '.' ) === false && ctype_digit( $cell['value'] ) ) {
                        $cell['value'] = (int)$cell['value'];
                    }
                }
            }

            if ( isset( $c->h ) ) {
                $cell['href'] = (string)$c->h;
            }

            $cells[ $cell['index'] ] = $cell;
        }

        // consolidate cells into rows
        $columns = array();
        foreach ( $cells as $c ) {
            $columns[ $c['index'] ] = $c;
        }

        ksort( $columns );

        return $columns;
    }

    public function toHTML( $worksheetIndex = 0 ) {
        $rows = $this->rows( $worksheetIndex );

        if ( $rows === false ) {
            return false;
        }

        $html = '<table>';

        foreach ( $rows as $r ) {
            $html .= '<tr>';
            if ( $this->rowsExReader ) {
                foreach ( $r as $c ) {
                    $html .= '<td>' . ( $c === '' ? '&nbsp;' : htmlspecialchars( $c['value'] ) ) . '</td>';
                }
            } else {
                foreach ( $r as $c ) {
                    $html .= '<td>' . ( $c === '' ? '&nbsp;' : htmlspecialchars( $c ) ) . '</td>';
                }
            }
            $html .= "</tr>\r\n";
        }

        $html .= '</table>';

        return $html;
    }

    public function worksheet( $worksheetIndex = 0 ) {

        if ( isset( $this->sheets[$worksheetIndex] ) ) {
            $ws = $this->sheets[$worksheetIndex];

            if ( isset( $ws['key'] ) && isset( $ws['data'] ) ) {
                return array(
                    'name' => $ws['name'],
                    'data' => $ws['data']
                );
            }

            $this->error( 'Worksheet ' . $worksheetIndex . ' not found. Try $xlsx->rows(' . $worksheetIndex . ')' );

            return false;
        }

        $this->error( 'Worksheet ' . $worksheetIndex . ' not found.' );

        return false;
    }

    public function getCell( $worksheetIndex = 0, $cell = 'A1' ) {

        if ( ( $ws = $this->worksheet( $worksheetIndex ) ) === false ) {
            return false;
        }
        $wsData = $this->sheets[$worksheetIndex]['data'];

        if ( isset( $wsData->sheetData->row ) ) {
            foreach ( $wsData->sheetData->row as $row ) {
                foreach ( $row->c as $c ) {
                    if ( (string)$c['r'] === $cell ) {
                        $c = $this->parseCell( $c );
                        return $c['value'];
                    }
                }
            }
        }

        return '';
    }

    private function parseCell( $cell ) {
        $c = array(
            'type'   => (string)$cell['t'],
            'value'  => '',
            'href'   => '',
            'f'      => '',
            'format' => '',
            'r'      => ''
        );

        $c['r'] = (string)$cell['r'];

        if ( isset( $cell['s'] ) ) {
            $c['format'] = $this->cellFormats[ (int)$cell['s'] ];
        }

        if ( isset( $cell->f ) ) {
            $c['f'] = (string)$cell->f;
        }

        if ( isset( $cell->v ) ) {
            if ( $cell['t'] == 's' ) {
                $c['value'] = $this->sharedstrings[ (int)$cell->v ];
            } else {
                $c['value'] = (string)$cell->v;

                if ( strpos( $c['value'], '.' ) === false && ctype_digit( $c['value'] ) ) {
                    $c['value'] = (int)$c['value'];
                }
            }
        }

        if ( isset( $cell->h ) ) {
            $c['href'] = (string)$cell->h;
        }

        return $c;
    }

    private function parseCellAddr( $cellAddr ) {
        preg_match( '/([A-Z]+)(\d+)/', $cellAddr, $m );
        $col = $m[1];
        $row = $m[2];

        $colLen = strlen( $col );
        $index  = 0;

        for ( $i = $colLen - 1; $i >= 0; $i-- ) {
            $index += ( ord( $col[$i] ) - 64 ) * pow( 26, $colLen - $i - 1 );
        }

        return array( $row - 1, $index - 1 );
    }

    public function getIndex( $cell = 'A1' ) {

        return $this->parseCellAddr( $cell );
    }

    public function setDateTimeFormat( $format ) {
        $this->datetimeFormat = $format;
    }
}
