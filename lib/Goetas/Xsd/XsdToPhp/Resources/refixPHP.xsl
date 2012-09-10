<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
xmlns:xsd="http://www.w3.org/2001/XMLSchema"
>


<xsl:template match="class/prop/class">

</xsl:template>


<xsl:template match="node()|@*" priority="-4">
	<xsl:copy>
		<xsl:apply-templates  select="@*"/>
		<xsl:apply-templates />
	</xsl:copy>
</xsl:template>

</xsl:stylesheet>
