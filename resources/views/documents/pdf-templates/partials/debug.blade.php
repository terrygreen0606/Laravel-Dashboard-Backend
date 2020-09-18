<% if (debug) { %>
<div class="page">
    <div class="page-header">
        <h1 class="header">Data</h1>
    </div>
    <ul class="debug">
        <% for (var prop in locals ) { %>
            <% if (Object.prototype.hasOwnProperty.call(locals , prop)) { %>
                <% if (typeof locals[prop] === 'object') { %>
                    <li><%- prop %>=
                        <ul>
                        <% for (var subprop in locals[prop] ) { %>
                            <% if (Object.prototype.hasOwnProperty.call(locals[prop] , subprop) && typeof locals[prop][subprop] !== 'function') { %>
                                <li><%- subprop %>:<%- locals[prop][subprop] %></li>
                            <% } %>
                        <% } %>
                        </ul>
                    </li>
                <% } else if (typeof locals[prop] !== 'function') { %>
                    <li><%- prop %>=<%- locals[prop] %></li>
                <% } %>
            <% } %>
        <% } %>
    </ul>
</div>
<% } %>