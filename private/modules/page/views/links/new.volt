{{ content() }}

<div class="container">
    <form method="post">
        <table class='table'>
            <tbody>
                <tr>
                    <td class="rightCell"><label>URL</label></td>
                    <td class="leftCell">{{ text_field("url",'size':'100') }}</td>
                </tr>
                <tr>
                    <td class="rightCell"><label>Title</label></td>
                     <td class="leftCell">{{ text_field("title",'size':'100') }}</td>
                </tr>
               <tr>
                    <td class="rightCell"><label>Site Name</label></td>
                  <td class="leftCell">{{ text_field("sitename",'size':'60') }}</td>
                </tr>
                <tr>
                    <td class="rightCell"><label>URL Type</label></td>
                    <td class="leftCell">
                        <select id="urltype" name="urltype">
                            <option value="remote">Remote</option>
                            <option value="local">Blog</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="rightCell"><label>Summary</label></td>
                  <td class="leftCell">{{ text_area("summary", 'cols':'100', 'rows':'4') }}</td>
                </tr>
                 <tr>
                    <td class="rightCell"></td>
                    <td>{{ submit_button("New") }}</td>
                </tr>
            </tbody>
        </table>
    </form>
</div>