<table class="blue-table">
    <tbody>
        <tr>
            <td class="header">Deck Area</td>
            <td><%= vessel.deckArea %> sq ft</td>
        </tr>
        <tr class="spacer"><td>&nbsp;</td><td>&nbsp;</td></tr>
        <tr>
            <td class="header">Water/foam requirement:</td>
            <td><%= calculator.getWaterFoamRequirement(vessel.deckArea)%> gallons/minute for 20 min</td>
        </tr>
        <tr>
            <td class="header">Extinguishing Agent Type(s):</td>
            <td>AFFF - Aer-O-Lite 3%</td>
        </tr>
        <tr class="spacer"><td>&nbsp;</td><td>&nbsp;</td></tr>
        <tr>
            <td class="header">Ports/COTP Zones Called:</td>
            <td>ALL COTP ZONES  (See Geographic-specific Appendix [GSA] for COTP Zone details)</td>
        </tr>
        <tr class="spacer"><td>&nbsp;</td><td>&nbsp;</td></tr>
        <tr>
            <td class="header">Largest Tank:</td>
            <td><%= calculator.getLargestTankInGallons(vessel.oilTankVolume) %> gallons</td>
        </tr>
        <tr>
            <td class="header">Heaviest Oil Group Carried:</td>
            <td>Group <%= vessel.oilGroup %></td>
        </tr>
       
        <tr>
            <td class="header">Pumping Requirements to empty in 24 hours:</td>
            <td><%= calculator.getPumpingRequirementPerHour(vessel.oilTankVolume) %> gallons/hour; <%= calculator.getPumpingRequirementPerMinute(vessel.oilTankVolume) %> gallons/minute</td>
        </tr>
        <tr>
            <td class="header">Pump(s) required:</td>
            <td>
                <p><%= calculator.getPump(vessel.oilGroup, vessel.oilTankVolume) %> TK150 Pump(s) &ndash; or other multiple pumps to achieve proper collective flowrate for tank size and cargo oil group carried.</p>

            <p>See <strong><em>Pumps &amp; Tugs Tables</em></strong> at the front of this section for alternate pumps with flowrates by tank size.  
            </td>
        </tr>
        <tr class="spacer"><td>&nbsp;</td><td>&nbsp;</td></tr>
        <tr>
            <td class="header">Deadweight:</td>
            <td><%= vessel.deadWeight %> mt</td>
        </tr>
        <tr>
            <td class="header">Approximate Tug Size Required for Vessel:</td>
            <td>
                <p><%= calculator.getTugSize(vessel.deadWeight) %> HP &ndash; or multiple smaller tugs.  </p>
                <p>See <strong><em>Pumps &amp; Tugs Tables</em></strong> at the front of this section for multiple tugs combinations.</p>
                <p>See GSA for listing of Tugs by Horsepower, Bollard Pull and Operating Environment Capabilities.</p>
            </td>
        </tr>
        <tr class="spacer"><td>&nbsp;</td><td>&nbsp;</td></tr>
        <tr>
            <td class="header">Vessel Pre-Fire Plan:</td>
            <td>See <a href="http://members.donjon-smit.com">http://members.donjon-smit.com</a></td>
        </tr>
        <tr class="spacer"><td>&nbsp;</td><td>&nbsp;</td></tr>
        <tr>
            <td class="header">MSDS(s) (Cargo &amp; Fuel):</td>
            <td>See <a href="http://www.donjon-smit.com/msds">http://www.donjon-smit.com/msds</a></td>
        </tr>
        <tr class="spacer"><td>&nbsp;</td><td>&nbsp;</td></tr>
        <tr>
            <td class="header">Damage Stability Provider:</td>
            <td>
                <%= locals.getVendorNameByType(vessel.vendors, 'DAMAGE_STABILITY_CERTIFICATE_PROVIDERS'); %>
            </td>
        </tr>
    </tbody>
</table>