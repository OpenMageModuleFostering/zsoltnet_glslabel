<?php

class ZsoltNet_GLSLabel_ReportsController extends Mage_Adminhtml_Controller_Action 
{
    public function indexAction() {
        echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"hu\" lang=\"hu\">\n";
        echo "<head>\n";
        echo "    <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" />\n";
        echo "    <script type=\"text/javascript\" src=\"/js/jquery/jquery-1.4.2.min.js\"></script>\n";
        echo "    <script type=\"text/javascript\" src=\"/js/glslabel/jquery.tablesorter.min.js\" ></script>\n";
        echo "    <script type=\"text/javascript\" src=\"/js/glslabel/jquery.tablesorter.pager.js\" ></script>\n";
        echo "    <link rel=\"stylesheet\" href=\"/js/glslabel/glsreports.css\" type=\"text/css\" media=\"print, projection, screen\" />\n";
        echo "    <script type=\"text/javascript\">\n";
        echo "    $(document).ready(function(){\n";
        echo "            $(\"#myTable\")\n";
        echo "            .tablesorter()\n";
        echo "            .tablesorterPager({container: $(\"#pager\")});\n";
        echo "    });\n";
        echo "    </script>\n";
        echo "</head>\n";
        echo "<body>\n";
        echo "<h1>GLS reports</h1>";

        $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : "";

        if ($id) {
            echo "<h2>report</h2>";
            $query  = "select id, jelentesszam, csomagszam, orderid, kiszallitva, osszeg from gls_utanvet where header_id='$id'" ;
            $result = Mage::getSingleton('core/resource')->getConnection('core_read')->fetchAll($query);
            $num    = count($result);
            echo "<table border='1' id='myTable' class='tablesorter'>";
            echo "<thead>";
            echo "<tr><th>jelentésszám</th><th>csomagszám</th><th>orderID</th><th>kiszálllítva</th><th>összeg</th></tr>";
            echo "</thead>";
            echo "<tbody>";
            $i=0;
            while ($i < $num) {
                $jelentesszam   = $result[$i]["jelentesszam"];
                $csomagszam     = $result[$i]["csomagszam"];
                $orderid        = $result[$i]["orderid"];
                $kiszallitva    = $result[$i]["kiszallitva"];
                $osszeg         = $result[$i]["osszeg"];

                //output html
                echo "<tr>";
                echo "<td>".$jelentesszam."</td>"; 
                echo "<td>".$csomagszam."</td>"; 
                echo "<td>".$orderid."</td>"; 
                echo "<td>".$kiszallitva."</td>"; 
                echo "<td>".$osszeg." Ft</td>"; 
                echo "</tr>";
                $i++;
            }
            echo "</tbody></table>";
            $query          = "select utalas_datuma, szumma from gls_header where id='$id'" ;
            $result         = mysql_query($query);
            $utalas_datuma  = mysql_result($result,0,"utalas_datuma");
            $szumma         = mysql_result($result,0,"szumma");
            echo "<p><br/>";
            echo "utalás dátuma: $utalas_datuma, szumma: $szumma Ft";
            echo "</p>";
        } else {
            echo "<h2>reportlista</h2>";
            $query  = 'select id, utalas_datuma, szumma from gls_header order by id desc;' ;
            $result = Mage::getSingleton('core/resource')->getConnection('core_read')->fetchAll($query);
            $num    = count($result);
            echo "<table border='1' id='myTable' class='tablesorter'>";
            echo "<thead>";
            echo "<tr><th>utalás dátuma</th><th>szumma</th></tr>";
            echo "</thead>";
            echo "<tbody>";
            $i=0;
            while ($i < $num) {
                $id             = $result[$i]["id"];
                $utalas_datuma  = $result[$i]["utalas_datuma"];
                $szumma         = $result[$i]["szumma"];

                //output html
                echo "<tr>";
                echo "<td><a href='?id=$id'>".$utalas_datuma."</a></td>"; 
                echo "<td>".$szumma." Ft</td>"; 
                echo "</tr>";
                $i++;
            }
            echo "</tbody></table>";
        }
        echo "<div id=\"pager\" class=\"pager\">\n";
        echo "    <form>\n";
        echo "         <img src=\"/js/glslabel/pager/first.png\" class=\"first\"/>\n";
        echo "         <img src=\"/js/glslabel/pager/prev.png\" class=\"prev\"/>\n";
        echo "         <input type=\"text\" class=\"pagedisplay\"/>\n";
        echo "         <img src=\"/js/glslabel/pager/next.png\" class=\"next\"/>\n";
        echo "         <img src=\"/js/glslabel/pager/last.png\" class=\"last\"/>\n";
        echo "         <select class=\"pagesize\">\n";
        echo "            <option selected=\"selected\" value=\"10\">10</option>\n";
        echo "            <option value=\"20\">20</option>\n";
        echo "         </select>\n";
        echo "    </form>\n";
        echo "</div>\n";
        echo "</body>\n";
        echo "</html>\n";
    }
}
